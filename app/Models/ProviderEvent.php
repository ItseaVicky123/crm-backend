<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class ProviderEvent extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'provider_event';

    /**
     * @var array
     */
    protected $visible = [
        'provider_type_id',
        'type_id',
        'account_id',
        'profile_id',
        'provider_name',
        'value',
        'is_active',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'type_id'    => 'event_type_id',
        'value'      => 'event_value',
        'is_active'  => 'active',
        'created_at' => self::CREATED_AT,
    ];

    /**
     * @var array
     */
    protected $appends = [
        'type_id',
        'value',
        'is_active',
        'created_at',
    ];

    /**
     * @var string[]
     */
    protected $guarded = [
        'id',
    ];
}
