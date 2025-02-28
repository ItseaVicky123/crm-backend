<?php


namespace App\Models\Affiliates;


use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\BaseModel;
use App\Lib\HasCreator;

/**
 * Class AffiliatePermission
 * @package App\Models\Affiliates
 */
class AffiliatePermission extends BaseModel
{
    use HasCreator;

    const CREATED_BY            = 'created_by';
    const UPDATED_BY            = 'updated_by';
    const ACCESS_TYPE_WHITELIST = 1;
    const ACCESS_TYPE_BLACKLIST = 2;

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'user_id',
        'affiliate_id',
        'access_type_id',
    ];

    /**
     * @var string[] $appends
     */
    protected $appends = [
        'access_type_verbose'
    ];

    /**
     * @var array|string[] $accessTypeMap
     */
    private array $accessTypeMap = [
        self::ACCESS_TYPE_WHITELIST => 'WHITELIST',
        self::ACCESS_TYPE_BLACKLIST => 'BLACKLIST',
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
     * Fetch the user that owns this affiliate permission.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'admin_id');
    }

    /**
     * Fetch the affiliate that owns this affiliate permission.
     * @return BelongsTo
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }

    /**
     * @return string
     */
    protected function getAccessTypeVerboseAttribute(): string
    {
        return isset($this->accessTypeMap[$this->access_type_id]) ? $this->accessTypeMap[$this->access_type_id] : '';
    }
}