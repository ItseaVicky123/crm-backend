<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class InternalChargebackTransaction
 * @package App\Models
 */
class InternalChargebackTransaction extends ValueAddTransaction
{
    protected $attributes = [
        'provider_type_id'    => ChargebackProviderAccount::PROVIDER_TYPE,
        'provider_account_id' => 10, //TODO - Once created InternalChargebackServiceProvider retrieve this value from there
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where('type_id', ChargebackProviderAccount::PROVIDER_TYPE)
               ->where('account_id', 10);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function value_add_transaction()
    {
        return $this->belongsTo(ValueAddTransaction::class, 'orders_id', 'orderId');
    }
}