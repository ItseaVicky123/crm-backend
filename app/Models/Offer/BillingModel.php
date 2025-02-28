<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;
use App\Models\BillingModel\BillingModel as BillingModelBase;
use App\Traits\HasCompositePrimaryKey;

/**
 * Class BillingModel
 * @package App\Models\Offer
 */
class BillingModel extends Model
{
    use Eloquence, Mappable, HasCompositePrimaryKey;

    const DEFAULT_ID = 2;

    /**
     * @var string
     */
    public $table = 'billing_subscription_frequency';

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
    protected $primaryKey = [
        'template_id',
        'frequency_id',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'template_id',
        'billing_model_id',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'billing_model',
        'discount',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'billing_model',
        'discount',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'billing_model_id' => 'frequency_id',
    ];

    protected ?BillingModelBase $billingModel = null;

    protected ?BillingModelDiscount $discount = null;

    public static function boot()
    {
        parent::boot();

        static::deleting(function($offerBillingModel) {
            $offerBillingModel->discount()->delete();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function billing_model()
    {
        return $this->hasOne(BillingModelBase::class, 'id', 'frequency_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function discount()
    {
        return $this->hasManyThrough(
            BillingModelDiscount::class,
            Offer::class,
            'template_id',
            'offer_id',
            'template_id'
        )->where('frequency_id', $this->attributes['frequency_id']);
    }

    /**
     * @return BillingModelDiscount|mixed|null
     */
    protected function getDiscountAttribute()
    {
        if (!isset($this->discount)) {
            $this->discount = $this->discount()->first();
        }

        return $this->discount;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $billingModel = $array['billing_model'] ?? [];

        unset($array['billing_model']);

        return array_merge($billingModel, $array);
    }

    /**
     * @return BillingModelBase|Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    protected function getBillingModelAttribute()
    {
        if (!isset($this->billingModel)) {
            $this->billingModel = $this->billing_model()->first();
        }

        return $this->billingModel;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        if ($key === 'discount') {
            return $this->getDiscountAttribute();
        } elseif ($key === 'billing_model') {
            return $this->getBillingModelAttribute();
        }

        return $this->billing_model->$key;
    }
}
