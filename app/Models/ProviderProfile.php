<?php

namespace App\Models;

use App\Traits\IsProviderProfile;
use App\Models\Campaign\Campaign;
use App\Traits\ModelReader;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProviderProfile
 * @package App\Traits
 */
class ProviderProfile extends Model
{
    use IsProviderProfile, ModelReader;

    public function afterSave()
    {
        return true;
    }

    protected function setGenericId()
    {
        if (in_array('generic_id', $this->fillable) && ! $this->getAttribute('generic_id')) {
            $this->generic_id = GenericFieldIndex::create(['name' => $this->account->name])->id;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function provider_object()
    {
        return $this->hasOne(ProviderObject::class, 'account_id', $this->getAccountIdColumn())
            ->where('provider_type_id', defined('static::PROVIDER_TYPE') ? static::PROVIDER_TYPE : 0);
    }

    /**
     * @return string
     */
    protected function getAccountIdColumn()
    {
        $accountColumn = 'account_id';

        if (isset($this->maps) && array_key_exists('account_id', $this->maps)) {
            $accountColumn = $this->maps['account_id'];
        }

        return $accountColumn;
    }

    protected function getGenericIdColumn()
    {
        $genericColumn = 'generic_id';

        if (isset($this->maps) && array_key_exists('generic_id', $this->maps)) {
            $genericColumn = $this->maps['generic_id'];
        }

        return $genericColumn;
    }

    /**
     * @return Model|null
     */
    protected function getProviderObjectAttribute()
    {
        return $this->provider_object()->first();
    }

    /**
     * @return mixed
     */
    public function provider_custom_fields()
    {
        return $this->hasMany(ProviderCustomField::class, 'account_id', $this->getAccountIdColumn());
    }

    /**
     * @return mixed
     */
    public function getProviderCustomFieldsAttribute()
    {
        return $this->provider_custom_fields()
            ->where('provider_type_id', static::PROVIDER_TYPE)
            ->where('profile_id', $this->id)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fields()
    {
        return $this->hasMany(GenericField::class, 'genericId', $this->getGenericIdColumn());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getGenericFieldsAttribute()
    {
        return $this->fields()->get();
    }

    /**
     * @return array
     */
    public function getLegacyCustomFieldsAttribute()
    {
        $custom_fields = [];

        $this->generic_fields
            ->each(function ($field) use (&$custom_fields) {
                if (substr($field->name, 0, 12) === 'Custom Field' && $field->value) {
                    $custom_fields [] = $field->value;
                }
            });

        return $custom_fields;
    }

    /**
     * @param array $fields
     */
    public function updateGatewayFields($fields)
    {
        $gatewayFields = [];

        foreach ($fields as $name => $value) {
            $gatewayFields[] = [
                'name'  => $name,
                'value' => $value,
            ];
        }

        foreach ($gatewayFields as $field) {
            if ($gf = $this->fields()->where('name', $field['name'])->first()) {
                $gf->update([
                    'value' => $field['value'] ?? '',
                ]);
            } else {
                $this->fields()
                    ->create([
                        'name'  => $field['name'],
                        'value' => $field['value'] ?? '',
                    ]);
            }
        }
    }

    /**
     * @param array $fields
     */
    public function updateGenericFields($fields)
    {
        $this->generic_fields
            ->each(function ($field) use ($fields) {
                if (array_key_exists($field->name, $fields)) {
                    $field->value = $fields[$field->name];
                    $field->save();
                }
            });
    }

    /**
     * @return array
     */
    protected function getAccountFieldValuesAttribute()
    {
        $result                = [];
        $accountRequiredFields = $this->account->account_fields;
        $values                = $this->generic_fields;
        $hashValues            = [];

        foreach ($values as $field) {
            $hashValues[preg_replace('/\s+/', '_', strtolower($field->name))] = $field->field_value;
        }

        foreach ($accountRequiredFields as $field) {
            $result[$field->api_name] = $hashValues[$field->api_name];

            if ($field->field_type_id == CustomFieldType::TYPE_ENUMERATION) {
                if ($field->is_multi) {
                    $result[$field->api_name] = $field->options()
                        ->whereIn('value', explode(',', $hashValues[$field->api_name]))
                        ->get();
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getGlobalFieldValuesAttribute()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getFieldsAttribute()
    {
        return [
            'global_fields'  => $this->global_field_values,
            'account_fields' => $this->account_field_values,
        ];
    }

    /**
     * @param array $campaigns
     * @return $this
     */
    public function attachCampaigns($campaigns = [])
    {
        if (count($campaigns)) {
            if ($providerObject = $this->provider_object) {
                if ($campaignColumn = $providerObject->provider_type->campaign_column) {
                    Campaign::whereIn('id', $campaigns)
                        ->update([$campaignColumn => $this->id]);
                }
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function order_profile()
    {
        return $this->morphToMany('App\Models\OrderProvider', 'profile');
    }
}
