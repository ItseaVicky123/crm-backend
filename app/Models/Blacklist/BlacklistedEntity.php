<?php

namespace App\Models\Blacklist;

use App\Models\BaseModel;
use App\Lib\HasCreator;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class BlacklistedEntity
 *
 * @package App\Models\BlacklistedEntity
 */
class BlacklistedEntity extends BaseModel
{
    use HasCreator;

    public const CREATED_BY  = 'created_by';

    public const UPDATED_BY  = 'updated_by';

    public const ACTIVE_FLAG = 'status';

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'rule_detail_id',
        'entity_type',
        'entity_id',
        'blacklisted_by',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'rule_detail_id',
        'entity_type',
        'entity_id',
        'blacklisted_by',
        'created_at',
        'updated_at',
    ];

    /**
     * Boot functions - what to set when an instance is created.
     * Hook into instance actions
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function ($instance) {
            $instance->blacklisted_by = get_current_user_id();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rule_details(): HasMany
    {
        return $this->hasMany(BlacklistRuleDetail::class, 'rule_detail_id', 'id');
    }
}
