<?php

namespace App\Models;

use App\Providers\PaymentServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\belongsTo;

class Verify3dTransaction extends ValueAddTransaction
{
    public const ACCOUNT_ID              = 153;
    public const OUTCOME_3D_ENROLLED_YES = 6;  // '3d_enrolled_yes',
    public const OUTCOME_3D_ENROLLED_NO  = 7;  // '3d_enrolled_no',
    public const OUTCOME_3D_PARAMS_RECVD = 8;  // '3d_params_received',
    public const OUTCOME_3D_PARAMS_SENT  = 9;  // '3d_params_sent',
    public const OUTCOME_3D_AUTH_SUCCESS = 10; // '3d_auth_success',
    public const OUTCOME_3D_AUTH_FAIL    = 11; // '3d_auth_failure',
    public const ACTION_ENROLLMENT_CHECK = 'enrollment_check';
    public const ACTION_3D_PARAMS_STATUS = '3d_params_status';
    public const ACTION_3D_AUTH_STATUS   = '3d_auth_status';

    /**
     * @var array
     */
    protected $attributes = [
        'provider_type_id'    => PaymentServiceProvider::PROVIDER_TYPE,
        'provider_account_id' => self::ACCOUNT_ID,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where('type_id', PaymentServiceProvider::PROVIDER_TYPE)
                ->where('account_id', self::ACCOUNT_ID);
        });
    }

    /**
     * @return belongsTo
     */
    public function value_add_transaction(): belongsTo
    {
        return $this->belongsTo(ValueAddTransaction::class, 'orders_id', 'orderId');
    }
}
