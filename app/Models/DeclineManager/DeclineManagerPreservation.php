<?php

namespace App\Models\DeclineManager;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class DeclineManagerPreservation
 * @package App\Models
 */
class DeclineManagerPreservation extends BaseModel
{
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

    /**
     * @return BelongsTo
     */
    protected function profile(): BelongsTo
    {
        return $this->belongsTo(SmartProfile::class, 'profile_id', 'id');
    }
}
