<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class PaymentMethod
 * Reader for the v_payment_methods view, uses slave connection.
 * @package App\Models\Payment
 */
class PaymentMethod extends Model
{
    use ModelImmutable;

    /**
     * Credit Card Offline Type
     */
    public const OFFLINE = 'offline';

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_payment_methods';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var int
     */
    public $perPage = 100;

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('greaterThanZero', fn (Builder $builder) => $builder->where('id', '>', 0));
    }

    /**
     * @return HasMany
     */
    public function payment_method_provider(): HasMany
    {
        return $this->hasMany(PaymentMethodProvider::class, 'payment_method_id', 'id');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeCreditCards($query)
    {
        return $query->where('is_cc_brand', 1);
    }
}
