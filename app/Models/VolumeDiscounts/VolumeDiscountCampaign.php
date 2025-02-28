<?php


namespace App\Models\VolumeDiscounts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BaseModel;

/**
 * Class VolumeDiscountCampaign
 * @package App\Models\VolumeDiscounts
 */
class VolumeDiscountCampaign extends BaseModel
{
    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'campaign_id',
        'volume_discount_id',
    ];

    /**
     * @return BelongsTo
     */
    public function volume_discount(): BelongsTo
    {
        return $this->belongsTo(VolumeDiscount::class, 'volume_discount_id');
    }

    /**
     * The getter for the volume_discount attribute.
     * @return VolumeDiscount|null
     */
    public function getVolumeDiscountAttribute(): ?VolumeDiscount
    {
        return $this->volume_discount()->get()->first();
    }
}
