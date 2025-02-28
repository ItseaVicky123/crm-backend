<?php

namespace App\Models\Campaign;

use App\Models\ApiUser;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Scopes\ActiveScope;

/**
 * Class Coupon
 * @package App\Models\Campaign
 */
class Coupon extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table = 'coupon_campaign_jct';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    public $fillable = [
        'coupon_id',
        'campaign_id',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'active' => 1,
    ];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ActiveScope);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function coupon()
    {
        return $this->hasOne(\App\Models\Coupon::class, 'id', 'coupon_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'c_id');
    }
}
