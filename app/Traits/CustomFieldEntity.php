<?php

namespace App\Traits;

use App\Models\CustomFieldType;
use App\Models\CustomFieldValue;
use App\Models\CustomField;
use Carbon\Carbon;
use App\Models\Prospect;
use App\Models\Customer;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exceptions\ResponseCodeException;
use App\Models\ValueAddService;
use \value_add_service_entry as VasLookup;

/**
 * Trait CustomFieldEntity
 * @package App\Traits
 */
trait CustomFieldEntity
{
    public function reduceEntityId($entityId = null)
    {
        $map = [
            Prospect::ENTITY_ID => Contact::ENTITY_ID,
            Customer::ENTITY_ID => Contact::ENTITY_ID,
        ];

        if (is_null($entityId)) {
            $entityId = static::ENTITY_ID;
        }

        return array_key_exists($entityId, $map)
            ? $map[$entityId]
            : $entityId;
    }

    /**
     * @return mixed
     */
    public function custom_field_values()
    {
        return $this->hasMany(CustomFieldValue::class, 'entity_id')->where('custom_field_values.entity_type_id', $this->reduceEntityId());
    }

    /**
     * @param       $model
     * @param array $custom_fields
     *
     * @throws ResponseCodeException
     */
    protected function handleCustomFields($model, $custom_fields = [], $updating = false)
    {
        if ($model instanceof Model) {
            $contacts       = [
                Prospect::ENTITY_ID,
                Customer::ENTITY_ID,
            ];
            $entity_id      = (in_array($model->entity_type_id, $contacts) ? $model->contact_id : $model->id);
            $entity_type_id = $this->reduceEntityId($model->entity_type_id);
            $current_count  = CustomFieldValue::where('entity_id', $entity_id)
                ->where('entity_type_id', $entity_type_id)
                ->get()
                ->count();

            if ($current_count >= CustomFieldValue::MAX_VALUES) {
                throw new ResponseCodeException('custom-fields.max-values', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $isSFCC = (new ValueAddService)->isEnabled(VasLookup::DEMANDWARE, true);

            foreach ($custom_fields as $custom_field_values) {
                $custom_field = null;

                if ($custom_field_values['token']) {
                    $custom_field = CustomField::where('token_key', $custom_field_values['token'])
                        ->where('entity_type_id', $entity_type_id)
                        ->first();
                } else {
                    $custom_field = CustomField::where('id', $custom_field_values['id'])
                        ->where('entity_type_id', $entity_type_id)
                        ->first();
                }

                if ($custom_field) {
                    if ($updating || !$custom_field->is_multi) {
                        $this->deleteCustomFieldValues($custom_field->id, $entity_id, $entity_type_id);
                    }

                    foreach ((array) $custom_field_values['value'] as $value) {
                        $option_id = 0;

                        if ($value) {
                            if ($custom_field->field_type_id == CustomFieldType::TYPE_ENUMERATION) {
                                $option_ids = [];

                                foreach ($custom_field->options as $option) {
                                    $option_ids[] = $option->id;

                                    if ($option->id == $value) {
                                        $option_id = $value;
                                        $value     = $option->value;
                                    }
                                }

                                if (! $option_id > 0) {
                                    throw new ResponseCodeException('custom-fields.invalid-option-id', Response::HTTP_UNPROCESSABLE_ENTITY);
                                }
                            } elseif ($custom_field->field_type_id == CustomFieldType::TYPE_NUMERIC) {
                                if (! is_numeric($value)) {
                                    throw new ResponseCodeException('custom-fields.invalid-value', Response::HTTP_UNPROCESSABLE_ENTITY);
                                }
                            } elseif ($custom_field->field_type_id == CustomFieldType::TYPE_DATE) {
                                try {
                                    $new_value = Carbon::parse($value)->toDateString();
                                } catch (\Exception $e) {
                                    throw new ResponseCodeException('custom-fields.invalid-value', Response::HTTP_UNPROCESSABLE_ENTITY);
                                }

                                $value = $new_value;
                            } elseif ($custom_field->field_type_id == CustomFieldType::TYPE_BOOLEAN) {
                                if (! is_numeric($value) || ! in_array($value, [0, 1])) {
                                    throw new ResponseCodeException('custom-fields.invalid-value', Response::HTTP_UNPROCESSABLE_ENTITY);
                                }
                            }

                            CustomFieldValue::withTrashed()->updateOrCreate([
                                // Primary key fields, used to identify existing record if possible
                                'custom_field_id' => $custom_field->id,
                                'entity_type_id'  => $entity_type_id,
                                'entity_id'       => $entity_id,
                                'option_id'       => $option_id,
                            ], [
                                'value'      => $value,
                                'deleted_at' => null,
                            ]);
                        }
                    }
                } elseif (!$isSFCC) {
                    throw new ResponseCodeException('custom-fields.invalid-request', Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
        }
    }

    /**
     * @param int $customFieldId
     * @param int $entityId
     * @param int $entityTypeId
     */
    protected function deleteCustomFieldValues($customFieldId = 0, $entityId = 0, $entityTypeId = 0)
    {
        $cfVals = CustomFieldValue::where('custom_field_id', $customFieldId)
            ->where('entity_type_id', $entityTypeId)
            ->where('entity_id', $entityId)
            ->get();

        if ($cfVals) {
            foreach ($cfVals as $cfVal) {
                $cfVal->delete();
            }
        }
    }

    /**
     * @param Request $request
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateCustomFieldRequest(Request $request)
    {
        $this->validate($request, [
            'custom_fields'         => 'required|array',
            'custom_fields.*.id'    => 'required_without:custom_fields.*.token|int',
            'custom_fields.*.token' => 'required_without:custom_fields.*.id',
            'custom_fields.*.value' => 'present',
        ]);
    }
}
