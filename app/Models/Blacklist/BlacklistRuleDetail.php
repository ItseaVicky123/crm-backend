<?php

namespace App\Models\Blacklist;

use App\Models\BaseModel;
use App\Lib\HasCreator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class BlacklistRuleDetail
 *
 * @package App\Models\BlacklistRuleV2
 */
class BlacklistRuleDetail extends BaseModel
{
    use SoftDeletes, HasCreator;

    public const CREATED_BY                       = 'created_by';

    public const UPDATED_BY                       = 'updated_by';

    public const ACTIVE_FLAG                      = 'status';

    public const APPLY_TO_ORDER                   = 1;

    public const APPLY_TO_PROSPECT                = 2;

    public const RULE_TYPE_IP                     = 1;

    public const RULE_TYPE_EMAIL                  = 2;

    public const RULE_TYPE_PHONE                  = 3;

    public const RULE_TYPE_ADDRESS                = 4;

    public const RULE_TYPE_API_PAYLOAD            = 5;

    public const RULE_TYPE_BIN_NUMBER             = 6;

    public const RULE_TYPE_DECLINE                = 7;

    public const RULE_TYPE_CHECKIN                = 8;

    public const RULE_TYPE_CC_NUMBER              = 9;

    public const ADDRESS_COMPONENT_TYPE_ADDRESS_1 = 1;

    public const ADDRESS_COMPONENT_TYPE_ADDRESS_2 = 2;

    public const ADDRESS_COMPONENT_TYPE_CITY      = 3;

    public const ADDRESS_COMPONENT_TYPE_STATE     = 4;

    public const ADDRESS_COMPONENT_TYPE_COUNTRY   = 5;

    public const ADDRESS_COMPONENT_TYPE_ZIPCODE   = 6;

    public const IP_COMPONENT_TYPE_IPV4           = 7;

    public const IP_COMPONENT_TYPE_IPV6           = 8;

    public const IP_COMPONENT_TYPE_GEO_LOCATION   = 9;

    public const CRITERIA_TYPE_EXACT_MATCH        = 1;

    public const CRITERIA_TYPE_REGEX              = 2;

    public const CRITERIA_TYPE_RANGE              = 3;

    public const CRITERIA_TYPE_SUBNET_MASK        = 4;

    public const CRITERIA_TYPE_DOMAIN             = 5;

    public const CRITERIA_TYPE_AREA_CODE          = 6;

    public const CRITERIA_TYPE_MULTIPLE           = 7;

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'type',
        'rule_id',
        'applied_to',
        'component_type',
        'criteria_type',
        'values',
        'range_min',
        'range_max',
        'decline_duration',
        'decline_attempts',
        'routing_num',
        'account_num',
        'rule_status',
        'status',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'type',
        'rule_id',
        'applied_to',
        'component_type',
        'criteria_type',
        'values',
        'range_min',
        'range_max',
        'decline_duration',
        'decline_attempts',
        'routing_num',
        'account_num',
        'rule_status',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
        'bin_values',
        'cc_mask',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var string[] $appends
     */
    protected $appends = [
        'bin_values',
        'cc_mask',
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bin_numbers(): HasMany
    {
        return $this->hasMany(BlacklistBinNumber::class, 'rule_detail_id');
    }

    /**
     * @return array
     */
    public function getBinValuesAttribute(): array
    {
        return $this->bin_numbers->pluck('value')->toArray();
    }

    /**
     * @return string
     */
    public function getCcMaskAttribute(): string
    {
        $cc_mask = '';
        if ($this->type == self::RULE_TYPE_CC_NUMBER && $this->criteria_type == self::CRITERIA_TYPE_EXACT_MATCH) {
            if (is_numeric($this->values) && strlen($this->values) < 20) {
                $cc_mask = \payment_source::get_cc_mask_from_card($this->values, true);
                self::update(['values' => \payment_source::encrypt_credit_card($this->values)]);
            } else {
                $cc_decrypt = \payment_source::decrypt_credit_card($this->values);
                $cc_mask    = (! empty($cc_decrypt) ? \payment_source::get_cc_mask_from_card($cc_decrypt, true) : '');
            }
        }

        return $cc_mask;
    }
}
