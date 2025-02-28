<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\Lime\LimeSelectableOptions;

/**
 * Class EmailSmtp
 * @package App\Models
 */
class EmailSmtp extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes, LimeSelectableOptions;

    /**
     * @var int
     */
    protected $perPage = 2000;

    /**
     * @var string
     */
    protected $table = 'notification_smtp';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'host',
        'domain',
        'email',
        'username',
        'password',
        'port',
        'mail_from',
        'use_authorization',
        'is_active',
        'is_authenticated',
        'updated_at',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_active'        => 'active',
        'is_deleted'       => 'deleted',
        'is_authenticated' => 'authenticated',
        'created_at'       => 'date_in',
        'updated_at'       => 'update_in',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'is_active',
        'is_authenticated',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'date_in',
        'update_in',
    ];

    /**
     * @var array
     */
    protected $searchableColumns = [
        'id',
        'name',
    ];
}
