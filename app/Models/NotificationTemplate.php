<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;

/**
 * Class NotificationTemplate
 * @package App\Models
 */
class NotificationTemplate extends Model
{

    use Eloquence, Mappable, LimeSoftDeletes, HasCreator;

    const UPDATED_BY      = 'updated_id';
    const DEFAULT_VERSION = 2;

    /**
     * @var int
     */
    public $perPage = 2000;

    /**
     * @var string
     */
    protected $table = 'notification_template';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'event_type_id',
        'notification_alert_days',
        'editor_version',
        'html_template',
        'text_template',
        'sms_template',
        'is_active',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'html_template'  => 'template_html',
        'text_template'  => 'template_plain',
        'is_active'      => 'active',
        'is_deleted'     => 'deleted',
        'created_at'     => 'date_in',
        'updated_at'     => 'update_in',
        'created_by'     => 'creator.name',
        'updated_by'     => 'updator.name',
        'editor_version' => 'editor.version',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'html_template',
        'text_template',
        'is_active',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'editor_version',
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

    /**
     * @var array
     */
    protected $attributes = [
        'version_id' => self::DEFAULT_VERSION,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function editor()
    {
        return $this->hasOne(EmailEditorVersion::class, 'id', 'version_id');
    }
}
