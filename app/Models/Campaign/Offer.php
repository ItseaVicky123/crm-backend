<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Traits\HasCompositePrimaryKey;

/**
 * Class Offer
 * @package App\Models\Campaign
 */
class Offer extends Model
{
    use Eloquence, HasCompositePrimaryKey;

    /**
     * @var string
     */
    protected $table = 'billing_campaign_offer';

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
    public $primaryKey = [
        'offer_id',
        'campaign_id',
    ];

    /**
     * @var array
     */
    public $fillable = [
        'offer_id',
        'campaign_id',
    ];
}
