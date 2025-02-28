<?php


namespace App\Models\Affiliates;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BaseModel;
use App\Lib\HasCreator;

/**
 * Class Affiliate
 * @package App\Models\Affiliates
 */
class Affiliate extends BaseModel
{
    use HasCreator;

    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'type_id',
        'value',
        'network',
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
     * Get users associated with this affiliate via permissions
     * @return HasManyThrough
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            AffiliatePermission::class,
            'affiliate_id',
            'admin_id',
            'id',
            'user_id',
        );
    }

    /**
     * Fetch affiliate permissions owned by this affiliate.
     * @return HasMany
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(AffiliatePermission::class, 'affiliate_id');
    }

    /**
     * Fetch the type that owns this affiliate.
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AffiliateType::class, 'type_id');
    }
}
