<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\HasCompositePrimaryKey;

/**
 * Class BillingModelDiscount
 * @package App\Models\Offer
 */
class BillingModelDiscount extends Model
{
    use Eloquence, Mappable, HasCompositePrimaryKey;

    /**
     * @var string
     */
    public $table = 'billing_offer_frequency_discount';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = [
        'offer_id',
        'frequency_id',
        'percent',
        'amount',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'percent',
        'amount',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'amount' => 'flat_amount',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'amount',
    ];


    /**
     * @var array
     */
    protected $primaryKey = [
        'offer_id',
        'frequency_id',
    ];

    /**
     * Determine if there is a discount configured.
     * @return bool
     */
    public function hasDiscount(): bool
    {
        return $this->amount > 0 || $this->percent > 0;
    }
}
