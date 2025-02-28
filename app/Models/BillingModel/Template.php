<?php

namespace App\Models\BillingModel;

use Illuminate\Database\Eloquent\Model;
use App\Models\Offer\Offer;

/**
 * Class Template
 * @package App\Models\BillingModel
 */
class Template extends Model
{
    const CREATED_AT =  'date_in';
    const UPDATED_AT = 'update_in';
    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    /**
     * @var string
     */
    protected $table = 'billing_subscription_template';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function billing_models()
    {
        return $this->belongsToMany(
            BillingModel::class,
            'billing_subscription_frequency',
            'template_id',
            'frequency_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function offer()
    {
        return $this->belongsTo(Offer::class, 'template_id', 'id');
    }
}
