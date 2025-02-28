<?php

namespace App\Models\DeclineManager;

use App\Models\BaseModel;

/**
 * Class DeclineSalvagePreservation
 * @package App\Models
 */
class DeclineSalvagePreserve extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'decline_salvage_preserve';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'order_id',
        'campaign_id',
        'attempt_number',
        'discount_percent',
        'created_at',
        'updated_at'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'order_id',
        'campaign_id',
        'attempt_number',
        'discount_percent',
        'created_at',
        'updated_at'
    ];
}
