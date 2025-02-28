<?php

namespace App\Models\Blacklist;

use App\Models\BaseModel;
use App\Lib\HasCreator;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Class BlacklistRule
 *
 * @package App\Models\BlacklistRule
 */
class BlacklistRule extends BaseModel
{
    use SoftDeletes, HasCreator;

    public const CREATED_BY  = 'created_by';

    public const UPDATED_BY  = 'updated_by';

    public const ACTIVE_FLAG = 'status';

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'formatted_created_at',
        'deleted_at',
        'rule_details',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'formatted_created_at',
        'rule_details',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Boot functions - what to set when an instance is created.
     * Hook into instance actions
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function ($instance) {
            $instance->created_by = get_current_user_id();
        });
        static::updating(function ($instance) {
            $instance->updated_by = get_current_user_id();
        });
        static::deleting(function ($instance) {
            $instance->updated_by = get_current_user_id();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rule_details(): HasMany
    {
        return $this->hasMany(BlacklistRuleDetail::class, 'rule_id', 'id');
    }

    /**
     * @return array
     */
    public function getRuleDetailsAttribute(): array
    {
        return $this->rule_details()->get()->toArray();
    }

    /**
     * @return string
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return Carbon::parse($this->created_at)->format('m/d/Y g:i A');
    }
}
