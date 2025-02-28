<?php

namespace App\Models\DeclineManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sofa\Eloquence\Eloquence;

/**
 * Class DeclineManagerAttemptConfiguration
 * @package App\Models
 */
class DeclineManagerAttemptConfiguration extends Model
{
    use Eloquence;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'attempt_number',
        'is_discount_preserve',
        'discount_percent',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'profile_id',
        'attempt_number',
        'discount_percent',
        'is_discount_preserve',
    ];

    /**
     * @return BelongsTo
     */
    protected function profile(): BelongsTo
    {
        return $this->belongsTo(SmartProfile::class, 'profile_id', 'id');
    }
}
