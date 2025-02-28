<?php


namespace App\Models;

use App\Models\Payment\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class Binbase
 * Reader for the v_binbase view, uses slave connection.
 * @package App\Models
 */
class Binbase extends Model
{

    use ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_binbase';

    /**
     * @var array
     */
    protected static $brandMap = [
        'AMERICAN EXPRESS'          => 'amex',
        'AMERICAN EXPRESS COMPANY'  => 'amex',
        'MASTERCARD'                => 'master',
        'DINERS CLUB INTERNATIONAL' => 'diners',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function payment_method()
    {
        return $this->hasOne(PaymentMethod::class, 'name', 'brand')
            ->where('v_payment_methods.is_cc_brand', 1);
    }

    /**
     * @return mixed
     */
    public function getBrandAttribute()
    {
        if (array_key_exists($this->attributes['brand'], self::$brandMap)) {
            return self::$brandMap[$this->attributes['brand']];
        }

        return $this->attributes['brand'];
    }
}
