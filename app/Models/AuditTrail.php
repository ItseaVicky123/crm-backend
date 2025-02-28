<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AuditTrail
 */
class AuditTrail extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table      = 'v_audit_trail';
    protected $primaryKey = 'hide_row_pk';

    public const HIDE_TABLE_BLACKLIST_RULES         = 'blacklist_rules';
    public const HIDE_TABLE_BLACKLIST_RULE_DETAILS  = 'blacklist_rule_details';

    /**
     * @var array
     */
    protected $visible = [
        'hide_table',
        'hide_row_pk',
        'field_name',
        'old_value',
        'new_value',
        'admin_fullname',
        'time_of_update',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'hide_table',
        'hide_row_pk',
        'field_name',
        'old_value',
        'new_value',
        'admin_fullname',
        'time_of_update',
    ];

    public static function boot()
    {
        parent::boot();

        self::creating(function ($instance) {
            $instance->created_by = $instance->created_by ?? get_current_user_id();
        });

        self::updating(function ($instance) {
            $instance->updated_by = $instance->updated_by ?? get_current_user_id();
        });
    }
}
