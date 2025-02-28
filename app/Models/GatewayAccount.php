<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class GatewayAccount
 * Reader for the v_gateway_accounts view, uses slave connection.
 * @package App\Models
 *
 * @property string $name
 * @property string $type
 *
 * @method static GatewayAccount findOrFail(int $id)
 */
class GatewayAccount extends ProviderAccount
{
    use Eloquence, Mappable, ModelImmutable;

    const PROVIDER_TYPE = 1;
    const COBRE_BEM = 62;
    const NMI_PAYSAFE = 181;
    const FLUID_PAY_PAYSAFE = 198;
    const TYPE_CC = 1;
    const TYPE_ALT = 2;
    const TYPE_CC_NAME = 'credit card';
    const TYPE_ALT_NAME = 'alternative';
    const PAYSAFE_IDS = [
        self::NMI_PAYSAFE,
        self::FLUID_PAY_PAYSAFE,
    ];

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;
    

    /**
     * @var string
     */
    protected $primaryKey = 'ga_id';

    /**
     * @var string
     */
    protected $table = 'v_gateway_accounts';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'type',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'name',
        'type',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'   => 'ga_id',
        'name' => 'account',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('is_active', 1);
        });
    }

    /**
     * @return string
     */
    public function getTypeAttribute(): string
    {
        return $this->type_id === self::TYPE_CC ? self::TYPE_CC_NAME : self::TYPE_ALT_NAME;
    }

    /**
     * @return HasMany
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(GatewayProfile::class, 'account_id');
    }

    /**
     * @return HasMany
     */
    public function supported_features(): HasMany
    {
        return $this->hasMany(GatewaySupportedFeatures::class, 'gateway_id');
    }

    /**
     * @return Collection
     */
    protected function getGlobalFieldsAttribute(): Collection
    {
        $global_fields = $this->global_fields()->get();
        $global_fields->push($this->currencies);

        return $global_fields;
    }

    /**
     * @return HasMany
     */
    public function currencies(): HasMany
    {
        return $this->hasMany(GatewayCurrency::class, 'gatewayId');
    }

    /**
     * @return array
     */
    public function getCurrenciesAttribute(): array
    {
        $currencies = [];

        $this->currencies()
            ->get()
            ->each(function ($currency) use (&$currencies) {
                $currencies[] = [
                    'id'     => $currency->currency->id,
                    'option' => $currency->currency->code,
                ];
            });

        return [
            'api_name'        => 'currency',
            'validation_rule' => Currency::VALIDATION_RULE,
            'options'         => $currencies,
        ];
    }

    /**
     * @return GatewayFeeType[]|Collection
     */
    protected function getFeeFieldsAttribute()
    {
        return GatewayFeeType::all();
    }

    /**
     * @return array
     */
    protected function getFieldsAttribute()
    {
        return [
            'global_fields'          => $this->global_fields,
            'account_fields'         => $this->account_fields,
            'fee_fields'             => $this->fee_fields,
            'provider_custom_fields' => $this->provider_object->provider_custom_fields,
        ];
    }

    /**
     * @param $type
     * @return array
     */
    public function getRequestValidationRules($type): array
    {
        $rules                      = $this->getBaseFieldsValidation($type);
        $rules['fields.fee_fields'] = 'array';

        $this->fee_fields->each(function ($feeField) use (&$rules) {
            $rules["fields.fee_fields.{$feeField->api_name}"] = $feeField->validation_rule;
        });

        if ($type === 'create') {
            $supported_currencies = [];

            $this->currencies()
                ->get()
                ->each(function ($g_currency) use (&$supported_currencies){
                    $supported_currencies[] = $g_currency->currency->id;
                });


            $rules['currency'] = Currency::VALIDATION_RULE . '|in:'. implode(',', $supported_currencies);
        }

        return $rules;
    }

    /**
     * @return bool
     */
    protected function getIsPaysafeAttribute()
    {
        return ($this->type_id == 1) && (in_array($this->id, self::PAYSAFE_IDS));
    }

    /**
     * @return bool
     */
    protected function getIsNmiPaysafeAttribute()
    {
        return (($this->type_id == 1) && ($this->id == self::NMI_PAYSAFE));
    }

    public function getPaymentTypesAttribute()
    {
        return Provider\PaymentType::forGatewayAccount($this)->get();
    }
}
