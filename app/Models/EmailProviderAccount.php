<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class EmailProviderAccount
 * Reader for the v_email_provider_accounts view, uses slave connection.
 * @package App\Models
 *
 * @property string $name
 */
class EmailProviderAccount extends ProviderAccount
{
    use Eloquence, Mappable, ModelImmutable;

    public const PROVIDER_TYPE = 4;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;
    

    /**
     * @var string
     */
    protected $table = 'v_email_provider_accounts';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * @return HasMany
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(EmailProviderProfile::class, 'emailProviderAccountId');
    }
}
