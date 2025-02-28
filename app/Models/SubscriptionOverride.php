<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\HasCreator;
use App\Models\Payment\ContactPaymentSource;
use Carbon\Carbon;

/**
 * Class Address
 * @package App\Models
 */
class SubscriptionOverride extends Model
{
    use SoftDeletes, HasCreator;

    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var array
     */
    protected $fillable = [
        'subscription_id',
        'address_id',
        'contact_payment_source_id',
        'promo_code',
        'created_by',
        'updated_by',
        'consumed_at',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'address',
        'contact_payment_source',
        'promo_code',
        'creator',
        'updator',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'consumed_at',
    ];

    /**
     * @var array
     */
    protected $with = [
        'address',
        'contact_payment_source',
    ];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('unused', function (Builder $builder) {
            $builder->whereNull('consumed_at');
        });

        self::creating(function ($override) {
            $override->created_by = get_current_user_id();
        });

        self::updating(function ($override) {
            if (!$override->getDirty('consumed_at')) {
                $override->updated_by = get_current_user_id();
            }
        });

        self::deleting(function ($override) {
            $override->updated_by = get_current_user_id();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function address()
    {
        return $this->hasOne(Address::class, 'id', 'address_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function contact_payment_source()
    {
        return $this->hasOne(ContactPaymentSource::class, 'id', 'contact_payment_source_id');
    }

    /**
     * @return bool
     */
    public function consume()
    {
        return $this->update(['consumed_at' => Carbon::now()->toDateTimeString()]);
    }
}
