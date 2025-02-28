<?php

namespace App\Models;

/**
 * Class Shipping
 * @package App\Models
 */
class Shipping extends BaseModel
{
    const CREATED_AT = 'date_in';
    const UPDATED_AT = 'update_in';

    /**
     * @var string
     */
    protected $table = 'shipping';

    /**
     * @var string
     */
    protected $primaryKey = 's_id';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'description',
        'amount_trial',
        'amount_recurring',
        'type',
        'service_code',
        'freight_code',
        'threshold_amount',
        'threshold_charge_amount',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'name',
        'description',
        'amount_trial',
        'amount_recurring',
        'type',
        'service_code',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'               => 's_id',
        'type_id'          => 's_type_id',
        'description'      => 's_description',
        'amount_trial'     => 's_trial_amount',
        'amount_recurring' => 's_recurring_amount',
        'name'             => 's_identity',
        'service_code'     => 'shipping_method_code',
        'created_at'       => 'date_in',
        'updated_at'       => 'update_in',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'amount_trial',
        'amount_recurring',
        'type_id',
        'service_code',
        'freight_code',
        'threshold_amount',
        'threshold_charge_amount',
        'created_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(ShippingType::class, 's_type_id', 's_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getTypeAttribute()
    {
        return $this->type()->first();
    }
}
