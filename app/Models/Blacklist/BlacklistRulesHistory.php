<?php

namespace App\Models\Blacklist;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;
/**
 * Class BlacklistRulesHistory
 */
class BlacklistRulesHistory extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table      = 'blacklist_history';

    protected $primaryKey = 'id';

    public const ACTION_TYPE_ORDER_BLACKLISTED      = 1;

    public const ACTION_TYPE_PROSPECT_BLACKLISTED   = 2;

    public const ACTION_TYPE_ORDER_UNBLACKLISTED    = 3;

    public const ACTION_TYPE_PROSPECT_UNBLACKLISTED = 4;

    /**
     * @var array
     */
    protected $visible = [
        'rule_detail_id',
        'type',
        'entity_type',
        'entity_id',
        'criteria_type',
        'component_type',
        'criteria_value',
        'observed_value',
        'decline_duration',
        'decline_attempts',
        'observed_decline_duration',
        'observed_decline_attempts',
        'routing_num',
        'account_num',
        'observed_routing_num',
        'observed_account_num',
        'action_type',
        'comments',
        'created_at',
        'updated_at',
        'formatted_created_at'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'rule_detail_id',
        'type',
        'entity_type',
        'entity_id',
        'criteria_type',
        'component_type',
        'criteria_value',
        'observed_value',
        'decline_duration',
        'decline_attempts',
        'observed_decline_duration',
        'observed_decline_attempts',
        'routing_num',
        'account_num',
        'observed_routing_num',
        'observed_account_num',
        'action_type',
        'comments',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'formatted_created_at'
    ];

    /**
     * @return string
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return Carbon::parse($this->created_at)->format('m/d/Y g:i A');
    }
}
