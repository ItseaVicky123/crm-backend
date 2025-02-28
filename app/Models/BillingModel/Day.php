<?php

namespace App\Models\BillingModel;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;

/**
 * Class Day
 * @package App\Models\BillingModel
 */
class Day extends Model
{
    use Eloquence, Mappable;

    /**
     * @var string
     */
    public $table = 'vlkp_product_subscription_day';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $maps = [
        'name' => 'value',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'name',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function billing_model()
    {
        return $this->belongsTo(BillingModel::class, 'interval_week', 'id');
    }
}
