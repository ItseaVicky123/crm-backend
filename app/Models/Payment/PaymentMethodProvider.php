<?php

namespace App\Models\Payment;

use App\Models\ProviderObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PaymentMethodProvider
 * Reader for the v_payment_method_provider view, uses slave connection.
 * @package App\Models\Payment
 */
class PaymentMethodProvider extends Model
{
    use ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_payment_method_provider';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return BelongsTo
     */
    public function payment_method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function provider_object(): BelongsTo
    {
        return $this->belongsTo(ProviderObject::class, ['account_id', 'provider_type_id'], ['provider_account_id', 'provider_type_id']);
    }
}
