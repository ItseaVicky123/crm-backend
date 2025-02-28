<?php

namespace App\Models;

use App\Lib\Lime\LimeSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class DataVerificationProviderProfile
 * @package App\Models
 *
 * @property string $id
 * @property string $alias
 * @property DataVerificationProviderAccount $account
 */
class DataVerificationProviderProfile extends ProviderProfile
{
    use Eloquence, Mappable, LimeSoftDeletes;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = 'update_in';
    const ACTIVE_FLAG = 'active';
    const DELETED_FLAG = 'deleted';
    const PROVIDER_TYPE = 15;

    /**
     * @var string
     */
    protected $table = 'data_verification_provider';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'account_id',
        'alias',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'account_id',
        'alias',
        'generic_id',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'account_name' => 'account.name',
        'created_at'   => self::CREATED_AT,
        'is_active'    => self::ACTIVE_FLAG,
        'is_deleted'   => self::DELETED_FLAG,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($profile) {
            $profile->setGenericId();
        });

        static::deleting(function ($profile){
            $profile->fields()->delete();
            $profile->provider_custom_fields()->delete();
        });
    }

    /**
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(DataVerificationProviderAccount::class, 'account_id');
    }
}
