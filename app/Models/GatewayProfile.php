<?php

namespace App\Models;

use App\Models\Campaign\Campaign;
use App\Scopes\ActiveScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class GatewayProfile
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $account_id   Gateway Account ID
 * @property string $account_name Gateway Account Name
 * @property string $alias        Gateway profile Alias
 * @property int $is_active
 * @property string $descriptor
 * @property string $merchant_id
 * @property string $customer_service_number
 * @property string $customer_service_email
 * @property string $customer_service_email_from
 * @property string $mid_group
 * @property GatewayProcessor $processor
 * @property GatewayVertical $vertical
 * @property GatewayAccount $account
 * @property array $fields
 * @property Currency $currency
 * @property int $archived_flag
 * @property Carbon $created_at
 *
 * @method static GatewayProfile|null find(int $id)
 * @method static GatewayProfile findOrFail(int $id)
 * @method static Builder withoutGlobalScopes(array $scopes = null)
 */
class GatewayProfile extends ProviderProfile
{

    use Eloquence, Mappable;

    const CREATED_AT    = 'createdOn';
    const UPDATED_AT    = null;
    const PROVIDER_TYPE = 1;

    /**
     * @var string
     */
    protected $primaryKey = 'gateway_id';

    /**
     * @var string
     */
    protected $table = 'gateway';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'account_id',
        'alias',
        'account_name',
        'currency',
        'fields',
        'createdOn',
        'current_monthly_amount',
        'processing_percent'
    ];

   /**
    * @var string[]
    */
    protected $fillable = [
        'account_id',
        'alias',
        'global_monthly_cap',
        'current_monthly_amount',
        'processing_percent',
        'reserve_percent',
        'transaction_fee',
        'chargeback_fee',
        'descriptor',
        'customer_service_number',
        'customer_service_email',
        'customer_service_email_from',
        'cascade_profile_id',
        'merchant_id',
        'mid_group',
        'processor_id',
        'reserve_term_id',
        'reserve_term_days',
        'reserve_cap',
        'cvv_type_id',
        'url',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'alias',
        'account_name',
        'currency',
        'fields',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'           => 'gateway_id',
        'alias'        => 'gatewayAlias',
        'account_name' => 'account.name',
        'is_active'    => 'active',
        'created_at'   => 'createdOn',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'current_monthly_amount' => 0,
        'processing_percent'     => 0,
        'reserve_percent'        => 0,
        'transaction_fee'        => 0,
        'chargeback_fee'         => 0,
        'cascade_profile_id'     => 0,
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope);

        static::deleting(function ($profile) {
            $profile->fees()->delete();
            $profile->vertical()->delete();
            $profile->fields()->delete();
        });
    }

    /**
     * @param      $request
     * @param      $originalGatewayAlias
     * @param bool $creating
     * @return array
     */
    protected function translateRequestArray($request, ?string $originalGatewayAlias = '', bool $creating = true): array
    {
        $verticalId     = 0;
        $baseProperties = [
            'alias' => $request['alias'] ?? $originalGatewayAlias,
        ];

        if ($creating) {
            $baseProperties['account_id'] = $request['account_id'];
        }

        if (isset($request['fields']['global_fields']['processor'])) {
            $baseProperties['processor_id'] = ProviderGlobalFieldOption::find($request['fields']['global_fields']['processor'])->value;
            unset($request['fields']['global_fields']['processor']);
        }

        if (isset($request['fields']['global_fields']['vertical'])) {
            $verticalId = ProviderGlobalFieldOption::find($request['fields']['global_fields']['vertical'])->value;
            unset($request['fields']['global_fields']['vertical']);
        }

        if (isset($request['fields']['global_fields']['reserve_term'])) {
            $baseProperties['reserve_term_id'] = ProviderGlobalFieldOption::find($request['fields']['global_fields']['reserve_term'])->value;
            unset($request['fields']['global_fields']['reserve_term']);
        }

        if (isset($request['fields']['global_fields']['cvv'])) {
            $baseProperties['cvv_type_id'] = ProviderGlobalFieldOption::find($request['fields']['global_fields']['cvv'])->value;
            unset($request['fields']['global_fields']['cvv']);
        }

        $all_fields_array = is_null($request['fields']['global_fields'])
            ? $baseProperties
            : array_merge($baseProperties, $request['fields']['global_fields']);

        return [
            'all_fields'  => $all_fields_array,
            'vertical_id' => $verticalId,
        ];
    }

    /**
     * @param $request
     * @return mixed
     */
    public function create($request)
    {
        $translate       = $this->translateRequestArray($request);
        $allFields       = $translate['all_fields'];
        $verticalId      = $translate['vertical_id'];
        $cancellationUrl = $allFields['cancellation_url'];

        unset($allFields['cancellation_url']);

        $profile                       = parent::create($allFields);
        $allFields['cancellation_url'] = $cancellationUrl;

        unset($allFields['alias'], $allFields['account_id']);

        $gatewayFields = [
            [
                'name'  => 'Currency',
                'value' => $request['currency'],
            ],
        ];

        if ($verticalId) {
            $profile->vertical()->create([
                'vertical_id' => $verticalId,
            ]);

            $gatewayFields[] = [
                'name'  => 'vertical id',
                'value' => $verticalId,
            ];
        }

        // Inserting Fees
        //
        $gatewayFees = [];

        if (! empty($request['fields']['fee_fields'])) {
            foreach ($request['fields']['fee_fields'] as $name => $value) {
                $gatewayFees[] = [
                    'type_id' => GatewayFeeType::where('api_name', $name)->first()->id,
                    'value'   => $value,
                ];
            }
        } else {
            GatewayFeeType::all()
                ->each(function ($type) use (&$gatewayFees) {
                    $gatewayFees[] = [
                        'type_id' => $type->id,
                        'value'   => 0,
                    ];
                });
        }

        $profile->fees()
            ->createMany($gatewayFees);

        // Inserting in gateway_fields
        //
        $mapNames = [];

        $profile->account
            ->account_fields()
            ->get()
            ->each(function ($field) use (&$mapNames) {
                $mapNames[$field->api_name] = [
                    $field->field_name,
                    $field->field_type_id,
                    $field->id,
                ];
            });

        if (count($mapNames)) {
            foreach ($request['fields']['account_fields'] as $name => $value) {
               [$fieldName, $typeId] = $mapNames[$name];
                if($fieldName != null) {
                    $gatewayFields[] = [
                        'name' => $fieldName,
                        'value' => $this->getFieldValue($typeId, $value),
                    ];
                }
            }
        }

        foreach ($allFields as $name => $value) {
            $name == ('merchant_id' ? 'mid merchant account id' : $this->translateField($name));
            if($name != null) {
                $gatewayFields[] = [
                    'name' => $name,
                    'value' => $profile->$name,
                ];
            }
        }

        foreach ($request['fields']['fee_fields'] as $name => $value) {
            if($name != null) {
                $gatewayFields[] = [
                    'name' => $this->translateField($name),
                    'value' => $request['fields']['fee_fields'][$name],
                ];
            }
        }

        $profile->fields()
            ->createMany($gatewayFields);

        return $profile;
    }

    /**
     * @param array $request
     * @param array $options
     * @return bool|void
     */
    public function update(array $request = [], array $options = [])
    {
        $translate       = $this->translateRequestArray($request, $this->attributes["gatewayAlias"], false);
        $allFields       = $translate['all_fields'];
        $verticalId      = $translate['vertical_id'];
        $cancellationUrl = null;

        if (isset($allFields['cancellation_url'])) {
            $cancellationUrl = $allFields['cancellation_url'];

            unset($allFields['cancellation_url']);
        }

        parent::update($allFields);

        if (isset($cancellationUrl)) {
            $allFields['cancellation_url'] = $cancellationUrl;
        }

        foreach (['alias', 'id'] as $toUnset) {
            if (isset($allFields[$toUnset])) {
                unset($allFields[$toUnset]);
            }
        }

        $gatewayFields = [];

        if ($verticalId) {
            $this->vertical->update([
                'vertical_id' => $verticalId,
            ]);

            $gatewayFields[] = [
                'name'  => 'vertical id',
                'value' => $verticalId,
            ];
        }

        //Updating Fees
        //
        if (! empty($request['fields']['fee_fields'])) {
            foreach ($request['fields']['fee_fields'] as $name => $value) {
                $this->fees()
                    ->updateOrCreate(
                        [
                            'type_id' => GatewayFeeType::where('api_name', $name)->first()->id,
                        ],
                        [
                            'value' => $value,
                        ]
                );
            }
        }

        // Updating in gateway_fields
        //
        $mapNames = [];

        $this->account
            ->account_fields()
            ->get()
            ->each(function ($field) use (&$mapNames) {
                $mapNames[$field->api_name] = [
                    $field->field_name,
                    $field->field_type_id,
                    $field->id,
                ];
            });

        if (count($mapNames)) {
            foreach ($request['fields']['account_fields'] as $name => $value) {
                if ($name == 'lime_light_3d_verify' && $value) {
                    $value = ValueAddServiceType::where('global_key', 'VAR_3DVERIFY')
                        ->first()
                        ->value_add_service
                        ->is_active;
                }

                [$fieldName, $typeId] = $mapNames[$name];

                $gatewayFields[] = [
                    'name'  => $fieldName,
                    'value' => $this->getFieldValue($typeId, $value),
                ];
            }
        }

        foreach ($allFields as $name => $value) {

            $nameVar = $name == 'merchant_id' ? 'mid merchant account id' : $this->translateField($name);

            $gatewayFields[] = [
                'name'  => $nameVar,
                'value' => $value,
            ];
        }

        foreach ($request['fields']['fee_fields'] as $name => $value) {
            $gatewayFields[] = [
                'name'  => $this->translateField($name),
                'value' => $request['fields']['fee_fields'][$name],
            ];
        }

        foreach ($gatewayFields as $field) {
            $this->fields()
                ->where('fieldName', $field['name'])
                ->update(['fieldValue' => $field['value']]);
        }
    }

    /**
     * @return mixed
     */
    public function getCurrencyIdAttribute()
    {
        return $this->fields()
            ->where('name', 'Currency')
            ->first()
            ->value;
    }

    /**
     * @return mixed
     */
    public function getCurrencyAttribute()
    {
        return Currency::find($this->currency_id);
    }

    /**
     * @return array
     */
    protected function getFieldsAttribute()
    {
        return [
            'global_fields'  => $this->global_field_values,
            'account_fields' => $this->account_field_values,
            'fee_fields'     => $this->fee_field_values,
        ];
    }

    /**
     * @return array
     */
    protected function getGlobalFieldValuesAttribute(): array
    {
        $cancellationField = $this->fields()
            ->where('name', '=', 'cancellation url')
            ->first();

        $cancellationUrl = $cancellationField ? $cancellationField->value : '';

        return [
            'global_monthly_cap'          => $this->global_monthly_cap,
            'customer_service_number'     => $this->customer_service_number,
            'descriptor'                  => $this->descriptor,
            'merchant_id'                 => $this->merchant_id,
            'customer_service_email'      => $this->customer_service_email,
            'customer_service_email_from' => $this->customer_service_email_from,
            'mid_group'                   => $this->mid_group,
            'vertical'                    => ProviderGlobalField::where('field_name', 'Vertical')
                ->first()
                ->options()
                ->where('value', $this->vertical->vertical_id)
                ->first(),
            'processor'                   => ProviderGlobalField::where('field_name', 'processor')
                ->first()
                ->options()
                ->where('value', $this->processor_id)
                ->first(),
            'url'                         => $this->url,
            'cancellation_url'            => $cancellationUrl,
            'cvv'                         => ProviderGlobalField::where('field_name', 'cvv')
                ->first()
                ->options()
                ->where('value', $this->cvv_type_id)
                ->first(),
            'transaction_fee'             => $this->transaction_fee,
            'chargeback_fee'              => $this->chargeback_fee,
            'reserve_percent'             => $this->reserve_percent,
            'reserve_term'                => ProviderGlobalField::where('field_name', 'reserve term')
                ->first()
                ->options()
                ->where('value', $this->reserve_term_id)
                ->first(),
            'reserve_term_days'           => $this->reserve_term_days,
            'reserve_cap'                 => $this->reserve_cap,
        ];
    }

    /**
     * @return array
     */
    public function getFeeFieldValuesAttribute()
    {
        $gateway_fees = [];

        $this->fees()->get()->each(function ($fee) use (&$gateway_fees)
        {
            $gateway_fees[$fee->fee->api_name] = $fee->value;
        });

        return $gateway_fees;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function vertical()
    {
        return $this->hasOne(GatewayVertical::class, 'gateway_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function processor()
    {
        return $this->hasOne(GatewayProcessor::class, 'id', 'processor_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cvv_type()
    {
        return $this->hasOne(GatewayCvvType::class, 'id', 'cvv_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function reserve_term()
    {
        return $this->hasOne(GatewayReserveTermType::class, 'id', 'reserve_term_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'gatewayId');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fees()
    {
        return $this->hasMany(GatewayFee::class, 'gateway_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function account()
    {
        return $this->hasOne(GatewayAccount::class, 'ga_id', 'account_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    protected function getAccountAttribute()
    {
        return $this->account()->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fields()
    {
        return $this->hasMany(GatewayField::class, 'gatewayId', 'gateway_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function field()
    {
        return $this->hasOne(self::class, 'gateway_id', 'gatewayId');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('active', 1);
    }

    /**
     * @param Builder $query
     * @param $id
     * @param string $operator
     * @return Builder|static
     */
    public function scopeHasCurrency(Builder $query, $id, $operator = '=')
    {
        return $query->whereHas('fields', function ($q) use ($id, $operator)
        {
            $q->where('name', 'Currency')
                ->where('value', $operator, $id);
        });
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeForAccount(Builder $query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::Class);
    }
}
