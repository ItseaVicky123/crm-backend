<?php

namespace App\Models\Contact;

use App\Models\BaseModel;
use App\Relations\Contacts\ContactCustomFieldValueRelation;
use App\Traits\CustomFieldEntity;
use Illuminate\Database\QueryException;
use App\Models\CustomField;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Base
 * @package App\Models\Contact
 */
class Base extends BaseModel
{
    use CustomFieldEntity;

    /**
     * @var int
     */
    public $entity_type_id = 0;

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            if (! $model instanceof Contact && ! Contact::where(['email' => $model->email])->exists()) {
                try {
                    Contact::create([
                        'email'      => $model->email,
                        'first_name' => $model->first_name,
                        'last_name'  => $model->last_name,
                        'phone'      => $model->phone,
                    ]);
                } catch (QueryException $e) {
                    if ($e->getCode() != 23000) {
                        \fileLogger::log_warning($e->getMessage());

                        if (defined('PHP_UNIT_TEST')) {
                            throw new QueryException($e->getSql(), $e->getBindings(), $e->getPrevious());
                        }
                    }
                }
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function custom_fields()
    {
        $custom_fields = CustomField::on($this->getConnectionName())
            ->where('entity_type_id', Contact::ENTITY_ID)
            ->get();

        foreach ($custom_fields as $custom_field) {
            $custom_field->entity_id = $this->getAttribute('contact_id');
            $custom_field->setAppends([
                'type_id',
                'values',
            ])->makeVisible([
                'values',
            ]);
        }

        $real_custom_fields = $custom_fields
            ->filter(function($field) {
                return (bool) $field->values->count();
            })
            ->values()
            ->all();

        $this->setRelation('custom_fields', $real_custom_fields);

        return $real_custom_fields;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|mixed
     */
    public function getCustomFieldsAttribute()
    {
        return (array_key_exists('custom_fields', $this->relations))
            ? $this->getRelation('custom_fields')
            : $this->custom_fields();
    }

    /**
     * @return mixed
     */
    public function getContactIdAttribute()
    {
        if (!$this->contact) {
            $this->contact = Contact::firstOrCreate([
                'email'      => $this->getAttribute('email'),
            ], [
                'first_name' => $this->getAttribute('first_name'),
                'last_name'  => $this->getAttribute('last_name'),
                'phone'      => $this->getAttribute('phone'),
            ]);
        }

        return $this->contact->getAttribute('id');
    }

    /**
     * @return mixed
     */
    public function getContactAttribute()
    {
        if (!$this->contact) {
            $this->contact = Contact::firstOrCreate([
                'email'      => $this->getAttribute('email'),
            ], [
                'first_name' => $this->getAttribute('first_name'),
                'last_name'  => $this->getAttribute('last_name'),
                'phone'      => $this->getAttribute('phone'),
            ]);
        }

        return $this->contact;
    }

    public function getCustomFieldsForLegacyAttribute()
    {
        $legacy = [];

        foreach ($this->getAttribute('custom_fields') as $field) {
            $legacy[] = $field->toArray();
        }

        return $legacy;
    }

    /**
     * @return ContactCustomFieldValueRelation
     */
    public function custom_field_values() : ContactCustomFieldValueRelation
    {
        return new ContactCustomFieldValueRelation($this);
    }

    /**
     * @return Collection
     */
    public function getCustomFieldValuesAttribute(): Collection
    {
        return $this->custom_field_values()->get();
    }
}
