<?php

namespace App\Traits;

use App\Exceptions\CustomModelException;
use App\Models\Campaign\Provider;
use App\Models\CustomFieldType;
use App\Models\ProviderCustomField;
use App\Models\ProviderRequiredFieldOption;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait IsProviderProfile
{
    /**
     * @param Request $request
     * @throws CustomModelException
     */
    public function handleProviderCustomFields(Request $request)
    {
        $validator = Validator::make($request->toArray(), [
            'fields.provider_custom_fields'         => 'array',
            'fields.provider_custom_fields.*.name'  => 'required|string|between:1,255',
            'fields.provider_custom_fields.*.value' => 'required|string|between:1,255|exists:mysql_slave.vlkp_postback_tokens,name',
        ]);

        if ($validator->fails()) {
            throw new CustomModelException('providers.invalid-request', $validator->errors());
        }

        if ($fields = $request->get('fields')['provider_custom_fields']) {
            $object = $this->provider_object;

            if (!$object->is_provider_custom_fields_enabled) {
                throw new CustomModelException('providers.custom_fields_not_enabled');
            }

            $maxFields = $object->provider_custom_fields_max;

            if ($maxFields && (count($fields) > $maxFields)) {
                throw new CustomModelException('providers.custom_fields_max', [
                    'provider_custom_fields_max' => $object->provider_custom_fields_max,
                ]);
            }

            $providerDefined = [];

            if ($providerDefinedFields = $object->provider_custom_fields) {
                if (!$providerDefinedFields['user_defined'] && $providerDefinedFields['fields']) {
                    foreach ($providerDefinedFields['fields'] as $field) {
                        $providerDefined[$field['name']] = $field;
                    }
                }
            }

            $names    = [];
            $toAppend = [
                'provider_type_id' => static::PROVIDER_TYPE,
                'profile_id'       => $this->id,
                'token_type_id'    => $this->provider_object->token_type_id,
            ];

            foreach ($fields as $key => &$field) {
                $arrayKey = "provider_custom_fields.{$key}.name";

                if (array_key_exists($field['name'], $names)) {
                    throw new CustomModelException('providers.invalid-request', [
                        $arrayKey => [
                            "The {$arrayKey} has a duplicate value to provider_custom_fields.{$names[$field['name']]}.name",
                        ],
                    ]);
                }

                if (count($providerDefined) && !array_key_exists($field['name'], $providerDefined)) {
                    throw new CustomModelException('providers.invalid-request', [
                        $arrayKey => [
                            "The {$arrayKey} has an invalid value",
                        ],
                    ]);
                }

                $field                 = array_merge($field, $toAppend);
                $names[$field['name']] = $key;
            }

            unset($field);

            ProviderCustomField::forProfile(static::PROVIDER_TYPE, $this->account_id, $this->id)
                ->delete();

            $this->provider_custom_fields()
                ->createMany($fields);
        }
    }

    /**
     * @param Request $request
     */
    public function createOrUpdateFields(Request $request)
    {
        $toCreate = [];
        $mapNames = [];
        $provider = $this->account ?? $this->provider;

        $provider
            ->account_fields
            ->each(function ($field) use (&$mapNames) {
                $mapNames[$field->api_name] = [
                    $field->field_name,
                    $field->field_type_id,
                    $field->id,
                    $field->is_multi,
                ];
            });

        if (($fields = $request->get('fields')) && array_key_exists('account_fields', $fields)) {
            foreach ($fields['account_fields'] as $key => $value) {
                [$fieldName, $typeId, $fieldId, $isMulti] = $mapNames[$key];

                $value = $this->getFieldValue($typeId, $value, $isMulti);

                if (!$this instanceof Service) {
                    $toCreate[] = [
                        [
                            'fieldName' => $fieldName,
                        ],
                        [
                            'fieldName'  => $fieldName,
                            'fieldValue' => $value,
                        ],
                    ];
                } else {
                    $toCreate[] = [
                        [
                            'field_id' => $fieldId,
                        ],
                        [
                            'field_id' => $fieldId,
                            'value'    => $value,
                        ],
                    ];
                }
            }
        }

        if (count($toCreate)) {
            foreach ($toCreate as $create) {
                $this->fields()
                    ->updateOrCreate($create[0], $create[1]);
            }
        }
    }

    /**
     * @param     $typeId
     * @param     $value
     * @param int $isMulti
     * @return string
     */
    protected function getFieldValue($typeId, $value, $isMulti = 0)
    {
        if ($typeId == CustomFieldType::TYPE_BOOLEAN) {
            if (in_array($value, [1, 'yes', 'Yes'])) {
                return 'yes';
            }

            return 'no';
        } elseif ($typeId == CustomFieldType::TYPE_ENUMERATION) {
            if ($isMulti && is_array($value)) {
                $values = ProviderRequiredFieldOption::whereIn('id', $value)
                    ->get()
                    ->pluck('value')
                    ->toArray();
                $value = implode(',', $values);
            } else {
                $value = ProviderRequiredFieldOption::findOrFail($value)->value;
            }
        }

        return $value;
    }

    /**
     * @param $field_name
     * @return string|string[]|null
     */
    protected function translateField($field_name)
    {
        return preg_replace(['/_/'], [' '], $field_name);
    }

   /**
    * @param array $campaigns
    * @return $this
    */
   public function attachCampaigns($campaigns = [])
   {
      if (count($campaigns)) {
         $base = [
            'profile_id'         => $this->id,
            'account_id'         => $this->account_id,
            'provider_type_id'   => self::PROVIDER_TYPE,
            'profile_generic_id' => $this->generic_id,
         ];

         foreach ($campaigns as $campaign) {
            $create = array_merge($base, [
               'campaign_id' => $campaign,
            ]);

            Provider::firstOrCreate($create, $create);
         }
      }

      return $this;
   }
}
