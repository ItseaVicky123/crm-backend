<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ValueAddTransaction
 * @package App\Models
 */
class ValueAddTransaction extends Model
{
    const CREATED_AT = 'date_in';
    const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'value_add_transaction';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'order_id',
        'action_type',
        'outcome',
        'quantity',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $guarded = [
        'id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'type_id'    => 'provider_type_id',
        'account_id' => 'provider_account_id',
        'created_at' => self::CREATED_AT,
    ];

    /**
     * @var array
     */
    protected $appends = [
        'created_at',
    ];

    /**
     * @var array
     */
    protected $with = [
        'value_add_outcome',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function value_add_outcome()
    {
        return $this->hasOne(ValueAddOutcome::class, 'id', 'outcome');
    }
}