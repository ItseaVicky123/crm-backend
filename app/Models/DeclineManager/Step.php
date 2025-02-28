<?php

namespace App\Models\DeclineManager;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;
use App\Traits\HasScheduleBits;

/**
 * Class Step
 * @package App\Models
 */
class Step extends Model
{

    use Mappable, Eloquence, HasScheduleBits;

    /**
     * @var string
     */
    public $table = 'decline_salvage_step';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $maps = [
        'is_discount_preserve' => 'discount_preserve',
        'discount_percent'     => 'discount_pct',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'is_discount_preserve',
        'discount_percent',
        'schedule_frequencies',
        'schedule_days',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'step',
        'schedule_type_id',
        'schedule_value',
        'schedule_frequencies',
        'schedule_days',
        'schedule_hour',
        'is_discount_preserve',
        'discount_percent',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'profile_id',
        'step',
        'discount_percent',
        'is_discount_preserve',
        'schedule_type_id',
        'schedule_value',
        'schedule_hour',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    protected function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    protected function type()
    {
        return $this->hasOne(RuleType::class, 'id', 'type_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    protected function getTypeAttribute()
    {
        return $this->type()->first();
    }
}
