<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class CouponPromoCode
 * @package App\Models
 */
class CouponPromoCode extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table = 'coupon_promo_code_jct';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function promo_code()
    {
        return $this->belongsTo(PromoCode::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
