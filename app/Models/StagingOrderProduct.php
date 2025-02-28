<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Offer\Offer;
use App\Models\BillingModel\BillingModel;

class StagingOrderProduct extends Model
{
    protected $guarded = [
       'id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function staging_order()
    {
        return $this->belongsTo(StagingOrder::class, 'staging_order_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function billing_model()
    {
        return $this->belongsTo(BillingModel::class);
    }
}
