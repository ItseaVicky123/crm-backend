<?php

namespace App\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class NotificationProviderAccount
 * Reader for the v_notification_provider_accounts view, uses slave connection.
 * @package App\Models
 */
class NotificationProviderAccount extends ProviderAccount
{
    use Eloquence, Mappable, ModelImmutable;

    const PROVIDER_TYPE = 21;
    const SMTP_TYPE     = 1;
    const SMS_TYPE      = 2;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;
    

    /**
     * @var string
     */
    protected $table = 'v_notification_provider_accounts';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'notification_type'
    ];

    protected $appends = [
        'notification_type',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function profiles()
    {
        return $this->hasMany(NotificationProviderProfile::class, 'account_id');
    }

    public function getNotificationTypeAttribute()
    {
        return $this->provider_attributes()
            ->where('attribute_name', 'notification_type_id')
            ->first()
            ->value;
    }
}
