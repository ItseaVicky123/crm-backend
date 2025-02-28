<?php

namespace App\Models;

use App\Lib\Lime\LimeSoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Class PromoCode
 * @package App\Models
 */
class PromoCode extends BaseModel
{
    use LimeSoftDeletes;

    const CREATED_AT = 'create_in';
    const UPDATED_AT = null;
    const CREATED_BY = 'create_id';
    const DELETED_FLAG = 'deleted';
    const ACTIVE_FLAG = 'active';

    /**
     * @var string
     */
    protected $table = 'promo_code';

    /**
     * @var string[]
     */
    protected $dates = [
        'created_at',
    ];

    /**
     * @var string[]
     */
    protected $visible = [
        'id',
        'code',
        'use_count',
        'is_active',
        'is_deleted',
        'created_at',
        'created_by',
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'code',
        'is_active',
        'is_deleted',
        'created_at',
        'created_by',
    ];

    /**
     * @var string[]
     */
    protected $maps = [
        'code'       => 'value',
        'is_active'  => self::ACTIVE_FLAG,
        'is_deleted' => self::DELETED_FLAG,
        'created_at' => 'create_in',
        'created_by' => self::CREATED_BY,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'code',
        'created_by',
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function($promoCode) {
            DB::delete(<<<'SQL'
DELETE
  FROM
      coupon_promo_code_jct
 WHERE
      promo_code_id = ?
SQL,
                [
                    $promoCode->id,
                ],
            );
        });

        static::creating(function($promoCode) {
            $promoCode->is_active  = 1;
            $promoCode->is_deleted = 0;
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_promo_code_jct')
            ->withPivotValue('active', 1);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function creator()
    {
        return $this->hasOne(User::class, 'admin_id',  self::CREATED_BY);
    }
}
