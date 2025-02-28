<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class PaymentType
 * Reader for the v_payment_types view, uses slave connection.
 * @package App\Models\Payment
 */
class PaymentType extends Model
{
    use ModelImmutable;

    const TYPE_CREDIT_CARD = 1;

    const TYPE_CHECKING = 2;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_payment_types';

    /**
     * @var bool
     */
    public $timestamps = false;
}
