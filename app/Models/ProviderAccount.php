<?php

namespace App\Models;

use App\Exceptions\CustomModelException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProviderAccount extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function provider_object()
    {
        return $this->hasOne(ProviderObject::class, 'account_id', $this->primaryKey)
            ->where('provider_type_id', static::PROVIDER_TYPE ?? 0);
    }

    /**
     * @return Model|null
     */
    protected function getProviderObjectAttribute()
    {
        return $this->provider_object()->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function provider_attributes()
    {
        return $this->hasMany(ProviderAttribute::class, 'provider_account_id')
            ->where('provider_type_id', static::PROVIDER_TYPE)
            ->where('is_active', 1);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function global_fields()
    {
        return $this->hasMany(ProviderField::class, 'provider_id')
            ->where('provider_type_id', static::PROVIDER_TYPE)
            ->where('is_visible', 1)
            ->where('is_super', 0)
            ->where('is_global', 1);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getGlobalFieldsAttribute()
    {
        return $this->global_fields()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function account_fields()
    {
        return $this->hasMany(ProviderField::class, 'provider_id')
            ->where('provider_type_id', static::PROVIDER_TYPE)
            ->where('is_super', 0)
            ->where('is_global', 0);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAccountFieldsAttribute()
    {
        return $this->account_fields()->get();
    }

    /**
     * @return array
     */
    protected function getFieldsAttribute()
    {
        return [
            'global_fields'          => $this->global_fields,
            'account_fields'         => $this->account_fields,
            'provider_custom_fields' => $this->provider_object->provider_custom_fields,
        ];
    }

    /**
     * @param $type
     * @return array
     */
    public function getRequestValidationRules($type): array
    {
        return $this->getBaseFieldsValidation($type);
    }

    /**
     * @param Request $request
     * @throws CustomModelException
     */
    public function handleProviderCustomFields(Request $request)
    {
        if ($this->provider_object->provider_custom_fields) {
            if ($fields = $request->get('provider_custom_fields')) {
                if (count($fields) > $this->provider_object->provider_custom_fields_max) {
                    throw new CustomModelException('provider.custom_field_max');
                }

                $this->provider_custom_fields()
                    ->createMany($fields);
            }
        }
    }

    public function provider_custom_fields()
    {
        return $this->hasMany(
            ProviderCustomField::class,
            [
                'provider_type_id',
                'account_id',
                'profile_id',
            ],
            [
                'provider_type_id',
                'account_id',
                'id',
            ]
        );
    }

    /**
     * @param $type
     * @return array
     */
    protected function getBaseFieldsValidation($type): array
    {
        $rules = [
            'alias'                => $this->global_fields()
                ->where('api_name', 'alias')
                ->first()
                ->validation_rule,
            'fields'               => 'required|array',
        ];

        $global_fields = ($type === 'create' ?
            $this->global_fields()
                ->where('api_name', '!=', 'alias')
                ->get():
            $this->global_fields()
                ->where('api_name', '!=', 'alias')
                ->where('is_read_only', 0)
                ->get()
        );

        if ($global_fields->count()) {
            $rules['fields.global_fields'] = 'required|array';

            $global_fields->each(function ($globalField) use (&$rules) {
                if ($globalField->field_type_id == CustomFieldType::TYPE_ENUMERATION) {
                    $supportedOptions = $globalField->options()
                        ->get()
                        ->pluck('id')
                        ->toArray();

                    $rules["fields.global_fields.{$globalField->api_name}"] = $globalField->validation_rule . '|in:'. implode(',', $supportedOptions);
                } else {
                    $rules["fields.global_fields.{$globalField->api_name}"] = $globalField->validation_rule;
                }
            });
        }

        $rules['fields.account_fields'] = 'required|array';

        $account_fields = ($type === 'create' ?
            $this->account_fields()
                ->where('is_visible', 1)
                ->where('api_name', '!=', 'alias') :
            $this->account_fields()
                ->where('api_name', '!=', 'alias')
                ->where('is_visible', 1)
                ->where('is_read_only', 0)
        );

        $account_fields->get()
            ->each(function ($accountField) use (&$rules) {
                if ($accountField->field_type_id == CustomFieldType::TYPE_ENUMERATION) {
                    $supportedOptions = $accountField->options()
                        ->get()
                        ->pluck('id')
                        ->toArray();

                    if ($accountField->is_multi) {
                        $rules["fields.account_fields.{$accountField->api_name}"]   = $accountField->validation_rule;
                        $rules["fields.account_fields.{$accountField->api_name}.*"] = 'in:'. implode(',', $supportedOptions);
                    } else {
                        $rules["fields.account_fields.{$accountField->api_name}"] = $accountField->validation_rule . '|in:'. implode(',', $supportedOptions);
                    }
                } else {
                    $rules["fields.account_fields.{$accountField->api_name}"] = $accountField->validation_rule;
                }
            });

        return $rules;
    }

    /**
     * @return mixed|string
     */
    protected function getImageUrlAttribute()
    {
        if ($url = $this->attributes['image_url']) {
            $url = CDN_SERVER . "/{$url}";
        }

        return $url ?? '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProfilesAttribute()
    {
        return $this->profiles()
            ->get()
            ->each(function ($profile) {
                $profile->makeHidden(['fields']);
            });
    }

    /**
     * @return mixed
     */
    public function value_add_service()
    {
        return $this->hasOneThrough(
            ValueAddService::class,
            ValueAddServiceProvider::class,
            'provider_account_id',
            'service_id',
            'account_id',
            'service_id'
        );
    }

    /**
     * @return array|null
     */
    protected function getValueAddConfigsAttribute()
    {
        if ($this->value_add_service) {
            return $this->value_add_service
                ->configurations()
                ->get()
                ->pluck('value', 'key')
                ->toArray();
        }

        return null;
    }
}
