<?php

namespace App\Models;

use App\Providers\ChargebackServiceProvider;
use Illuminate\Database\Eloquent\Model;

class ChargebackServiceAlert extends Model
{
    protected $table = 'chargeback_service_alert';

    const CREATED_AT = 'date_in';
    const UPDATED_AT = null;

    public const PROVIDER_TYPE = ChargebackServiceProvider::PROVIDER_TYPE;

    protected $visible = [
        'order_id',
        'service_id',
        'provider_account_id',
        'provider_type_id',
        'alert_type_id',
        'alert_type_name',
        'outcome',
        'alert_date_in',
        'age_hours',
        'provider_alert_id',
        'card_issuer',
        'cc_first_6',
        'cc_last_4',
        'arn',
        'transaction_date_in',
        'merchant_descriptor',
        'member_id',
        'merch_category_code',
        'transaction_amount',
        'currency',
        'transaction_type',
        'initiated_by',
        'provider_source',
        'auth_code',
        'merch_member_name',
        'provider_transaction_id',
        'reason_code',
        'three_d_secure_status',
        'chargeback_amount',
        'chargeback_currency',
        'provider_case_number',
        'date_in',
        'provider_status',
        'provider_code',
        'cc_type',
        'provider_message',
        'request_id',
        'external_order_id',
        'pricing_tier',
        'visa_transaction_id',
        'installment_number',
    ];

    protected $fillable = [
        'order_id',
        'service_id',
        'provider_account_id',
        'provider_type_id',
        'alert_type_id',
        'alert_type_name',
        'outcome',
        'alert_date_in',
        'age_hours',
        'provider_alert_id',
        'card_issuer',
        'cc_first_6',
        'cc_last_4',
        'arn',
        'transaction_date_in',
        'merchant_descriptor',
        'member_id',
        'merch_category_code',
        'transaction_amount',
        'currency',
        'transaction_type',
        'initiated_by',
        'provider_source',
        'auth_code',
        'merch_member_name',
        'provider_transaction_id',
        'reason_code',
        'three_d_secure_status',
        'chargeback_amount',
        'chargeback_currency',
        'provider_case_number',
        'date_in',
        'provider_status',
        'provider_code',
        'cc_type',
        'provider_message',
        'request_id',
        'external_order_id',
        'pricing_tier',
        'visa_transaction_id',
        'installment_number',
    ];
}
