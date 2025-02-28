<?php


namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompositePrimaryKey;

/**
 * Class PrepaidProfileTerm
 * @package App\Models\Offer
 */
class PrepaidProfileTerm extends Model
{
    use HasCompositePrimaryKey;

    /**
     * @var string
     */
    public $table = 'billing_offer_prepaid_terms';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'discount_type',
        'discount_value',
        'cycles',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'profile_id',
        'discount_type_id',
        'discount_value',
        'cycles',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'discount_type',
    ];

    /**
     * @var array
     */
    protected $primaryKey = [
        'profile_id',
        'cycles',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function discount_type()
    {
        return $this->hasOne(PrepaidDiscountType::class, 'id', 'discount_type_id');
    }

    /**
     * Determine if the prepaid term is an percentage-based discount type.
     * @return bool
     */
    public function isPercentDiscountType(): bool
    {
        return $this->discount_type->id == PrepaidDiscountType::TYPE_PERCENT;
    }

    /**
     * Determine if the prepaid term is an amount-based discount type.
     * @return bool
     */
    public function isAmountDiscountType(): bool
    {
        return $this->discount_type->id == PrepaidDiscountType::TYPE_AMOUNT;
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    protected function getDiscountTypeAttribute()
    {
        return $this->discount_type()->first();
    }
}
