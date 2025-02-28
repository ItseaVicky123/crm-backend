<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class EmailProviderProfile
 * @package App\Models
 *
 * @property int $id
 * @property string $alias
 * @property EmailProviderAccount $account
 */
class EmailProviderProfile extends ProviderProfile
{
    use Eloquence, Mappable;

    public const CREATED_AT    = 'createdOn';
    public const UPDATED_AT    = null;
    public const PROVIDER_TYPE = 4;

    /**
     * @var string
     */
    protected $table = 'emailProvider';

    /**
     * @var string
     */
    protected $primaryKey = 'emailProviderId';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'account_id',
        'alias',
        'account_name',
        'fields',
        'provider_custom_fields',
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
        'id',
        'account_id',
        'account_name',
        'fields',
        'provider_custom_fields',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'           => 'emailProviderId',
        'account_name' => 'account.name',
        'account_id'   => 'emailProviderAccountId',
        'generic_id'   => 'genericId',
        'is_active'    => 'active',
        'created_at'   => self::CREATED_AT,
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope);

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
        return $this->belongsTo(EmailProviderAccount::class, 'account_id');
    }
}
