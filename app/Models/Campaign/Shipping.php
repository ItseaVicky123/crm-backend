<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class Shipping
 * @package App\Models\Campaign
 */
class Shipping extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table = 'campaign_shipping';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'campaign_id',
        'shipping_id',
        'is_upsell',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function shipping()
    {
        return $this->hasOne(Shipping::class, 's_id', 'shipping_id');
    }
}
