<?php

namespace App\Lib\Blacklist\ModuleRequests;

use App\Lib\ModuleHandlers\ModuleRequest;
use App\Models\Blacklist\BlacklistRuleDetail;
use Illuminate\Validation\ValidationException;
use App\Lib\Traits\HasBlacklist;

/**
 * Class SaveRuleDetailRequest
 *
 * @package App\Lib\Blacklist\ModuleRequests
 */
class SaveRuleDetailRequest extends ModuleRequest
{
    use HasBlacklist;

    /**
     * @var bool $isCreate
     */
    protected bool $isCreate = true;

    /**
     * SaveRuleDetailRequest constructor.
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $rules        = [
            'applied_to' => "required|int",
            'type'       => "required|int",
        ];

        $attributes = [
            'type'             => 'Type',
            'applied_to'       => 'Applied To',
            'component_type'   => 'Component Type',
            'decline_attempts' => 'Decline Attempts',
            'decline_duration' => 'Decline Duration',
            'routing_num'      => 'Routing Number',
            'account_num'      => 'Account Number',
            'range_min'        => 'Range Min',
            'range_max'        => 'Range Max',
            'values'           => 'Values',
            'criteria_type'    => 'Criteria',
            'id'               => 'Blacklist Rule Detail ID',
        ];

        $ruleTypesArrForComponentType = [BlacklistRuleDetail::RULE_TYPE_ADDRESS, BlacklistRuleDetail::RULE_TYPE_IP];

        if (in_array($data['type'], $ruleTypesArrForComponentType)) {
            $rules['component_type'] = "required|int";
        }

        if ($data['type'] == BlacklistRuleDetail::RULE_TYPE_DECLINE) {
            $rules['decline_attempts'] = "required|int";
            $rules['decline_duration'] = "required|int";
        }

        if ($data['type'] == BlacklistRuleDetail::RULE_TYPE_CHECKIN) {
            $rules['routing_num'] = "required|int";
            $rules['account_num'] = "required|int";
        }

        if (in_array($data['type'], $this->criteriaBasedRuleTypes())) {
            $rules['criteria_type'] = "required";

            if($data['criteria_type'] != BlacklistRuleDetail::CRITERIA_TYPE_RANGE) {
                $rules['values']      = "required";
            }
        }

        if (in_array($data['type'], $this->criteriaBasedRuleTypes()) && $data['criteria_type'] == BlacklistRuleDetail::CRITERIA_TYPE_RANGE) {
            $rules['range_min'] = "required";
            $rules['range_max'] = "required";
        }

        if (! $this->isCreate) {
            $rules['id'] = 'required|int|exists:mysql_slave.blacklist_rule_details,id';
        }

        $this->validate($rules, $attributes);
    }
}
