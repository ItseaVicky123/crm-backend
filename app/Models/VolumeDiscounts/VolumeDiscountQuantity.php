<?php


namespace App\Models\VolumeDiscounts;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class VolumeDiscountQuantity
 * @package App\Models\VolumeDiscounts
 */
class VolumeDiscountQuantity extends BaseModel
{
    const TYPE_PERCENT = 1;
    const TYPE_DOLLAR  = 2;

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'volume_discount_id',
        'lower_bound',
        'upper_bound',
        'discount_type_id',
        'amount',
    ];

    /**
     * Get the volume discount that owns this volume discount quantity.
     * @return BelongsTo
     */
    public function volume_discount(): BelongsTo
    {
        return $this->belongsTo(VolumeDiscount::class, 'volume_discount_id');
    }

    /**
     * @return bool
     */
    public function isPercent(): bool
    {
        return $this->discount_type_id == self::TYPE_PERCENT;
    }

    /**
     * @return bool
     */
    public function isDollarAmount(): bool
    {
        return $this->discount_type_id == self::TYPE_DOLLAR;
    }

    /**
     * @param float $amount
     * @return float
     */
    public function getDiscountFromAmount(float $amount): float
    {
        $discountAmount = 0;

        if ($amount > 0 && $this->amount > 0) {
            if ($this->isDollarAmount()) {
                $discountAmount = $this->amount;
            } else if ($this->isPercent()) {
                $discountAmount = round(($this->amount / 100) * $amount, 4);
            }
        }

        return $discountAmount;
    }
}
