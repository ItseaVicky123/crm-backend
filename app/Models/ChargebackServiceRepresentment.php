<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;


/**
 * Class ChargebackServiceRepresentment
 * @package App\Models
 */
class ChargebackServiceRepresentment extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'chargeback_service_representment';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'service_id',
        'type_id',
        'account_id',
        'transaction_id',
        'order_id',
    ];

    protected $fillable = [
        'order_id',
        'service_id',
        'provider_account_id',
        'provider_type_id',
        'outcome',
        'cc_first_6',
        'cc_last_4',
        'arn',
        'transaction_date_in',
        'currency',
        'provider_transaction_id',
        'reason_code',
        'dispute_amount',
        'case_number',
        'case_type',
        'provider_status',
        'provider_verdict',
        'first_name',
        'last_name',
        'billing_address_1',
        'billing_city',
        'billing_state',
        'billing_zip',
        'posted_date_in',
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
    protected $appends = [
        'type_id',
        'account_id',
        'transaction_id',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'type_id'        => 'provider_type_id',
        'account_id'     => 'provider_account_id',
        'transaction_id' => 'provider_transaction_id',
        'created_at'     => self::CREATED_AT,
        'orderId'        => 'order_id',
        'caseId'         => 'case_number',
        'outcome'        => 'provider_verdict'
    ];
}
