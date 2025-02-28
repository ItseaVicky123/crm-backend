<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Product;

/**
 * Class CollectionOfferProduct
 *
 * @package App\Models\Offer
 */
class CollectionOfferProduct extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'product_id',
        'product_unit_price',
        'product_qty',
        'position_id',
        'is_locked',
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
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * @return BelongsTo
     */
    public function collection_offer(): BelongsTo
    {
        return $this->belongsTo(CollectionOffer::class);
    }

    public function setProductQtyAttribute($value)
    {
        $this->attributes['product_qty'] = $value ?? 1;
    }
}
