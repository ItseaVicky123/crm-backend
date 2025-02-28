<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class OfferSupplementalProduct
 *
 * @package App\Models\Offer
 */
class OfferSupplementalProduct extends Model
{
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'product_qty',
        'product_id',
        'trigger_count',
        'product_qty',
        'product_price',
    ];

    /**
     * @var string[]
     */
    protected $with = [
        'product'
    ];

    /**
     * @return BelongsTo
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
