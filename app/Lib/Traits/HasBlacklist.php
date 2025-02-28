<?php

namespace App\Lib\Traits;

use App\Models\Blacklist\BlacklistRule;
use App\Models\Blacklist\BlacklistRuleDetail;
use App\Lib\Blacklist\ModuleRequests\SaveRuleDetailRequest;

/**
 * Class HasBlacklist
 *
 * @package App\Lib\Traits
 */
trait HasBlacklist
{
    /**
     * @var BlacklistRule|null $blacklistRule
     */
    protected ?BlacklistRule $blacklistRule = null;

    /**
     * @var BlacklistRuleDetail|null $blacklistRuleDetail
     */
    protected ?BlacklistRuleDetail $blacklistRuleDetail = null;

    /**
     * @return BlacklistRule|null
     */
    public function getBlacklist(): ?BlacklistRule
    {
        return $this->blacklistRule;
    }

    /**
     * Load the blacklist rule model.
     *
     * @param int $id
     * @return $this
     */
    public function setBlacklist(int $id): self
    {
        $this->blacklistRule = BlacklistRule::findOrFail($id);

        return $this;
    }

    /**
     * @return BlacklistRuleDetail|null
     */
    public function getBlacklistDetail(): ?BlacklistRuleDetail
    {
        return $this->blacklistRuleDetail;
    }

    /**
     * Load the blacklist rule detail model.
     *
     * @param int $id
     * @return $this
     */
    public function setBlacklistDetail(int $id): self
    {
        $this->blacklistRuleDetail = BlacklistRuleDetail::findOrFail($id);

        return $this;
    }

    /**
     * Criteria based rule types
     *
     * @return array
     */
    public static function criteriaBasedRuleTypes(): array
    {
        return [
            BlacklistRuleDetail::RULE_TYPE_IP,
            BlacklistRuleDetail::RULE_TYPE_EMAIL,
            BlacklistRuleDetail::RULE_TYPE_PHONE,
            BlacklistRuleDetail::RULE_TYPE_ADDRESS,
            BlacklistRuleDetail::RULE_TYPE_API_PAYLOAD,
            BlacklistRuleDetail::RULE_TYPE_BIN_NUMBER,
            BlacklistRuleDetail::RULE_TYPE_CC_NUMBER,
        ];
    }

    /**
     * Static Values for Apply To
     *
     * @return array
     */
    public static function applyToArray(): array
    {
        return [
            BlacklistRuleDetail::APPLY_TO_ORDER,
            BlacklistRuleDetail::APPLY_TO_PROSPECT,
        ];
    }

    /**
     * Static Values for Rule Type
     *
     * @return array
     */
    public static function ruleTypeArray(): array
    {
        return [
            BlacklistRuleDetail::RULE_TYPE_IP,
            BlacklistRuleDetail::RULE_TYPE_EMAIL,
            BlacklistRuleDetail::RULE_TYPE_PHONE,
            BlacklistRuleDetail::RULE_TYPE_ADDRESS,
            BlacklistRuleDetail::RULE_TYPE_API_PAYLOAD,
            BlacklistRuleDetail::RULE_TYPE_BIN_NUMBER,
            BlacklistRuleDetail::RULE_TYPE_DECLINE,
            BlacklistRuleDetail::RULE_TYPE_CHECKIN,
            BlacklistRuleDetail::RULE_TYPE_CC_NUMBER,
        ];
    }

    /**
     * Static Values for Address Component Type
     *
     * @return array
     */
    public static function addressComponentTypeArray(): array
    {
        return [
            BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_ADDRESS_1,
            BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_ADDRESS_2,
            BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_CITY,
            BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_STATE,
            BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_COUNTRY,
            BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_ZIPCODE,
        ];
    }

    /**
     * Static Values for IP Component
     *
     * @return array
     */
    public static function ipComponentTypeArray(): array
    {
        return [
            BlacklistRuleDetail::IP_COMPONENT_TYPE_IPV4,
            BlacklistRuleDetail::IP_COMPONENT_TYPE_IPV6,
            BlacklistRuleDetail::IP_COMPONENT_TYPE_GEO_LOCATION,
        ];
    }

    /**
     * @param int $applyTo
     * @return bool
     */
    public function isApplyToCorrect(int $applyTo): bool
    {
        return in_array($applyTo, self::applyToArray());
    }

    /**
     * @param int $ruleType
     * @return bool
     */
    public function isRuleTypeCorrect(int $ruleType): bool
    {
        return in_array($ruleType, self::ruleTypeArray());
    }

    /**
     * @param int $addressComponentType
     * @return bool
     */
    public function isAddressComponentTypeCorrect(int $addressComponentType): bool
    {
        return in_array($addressComponentType, self::addressComponentTypeArray());
    }

    /**
     * @param int $ipComponentType
     * @return bool
     */
    public function isIPComponentTypeCorrect(int $ipComponentType): bool
    {
        return in_array($ipComponentType, self::ipComponentTypeArray());
    }

    /**
     * @param int $type
     * @param int $criteriaType
     * @return bool
     */
    public function isCriteriaTypeCorrect(int $type, int $criteriaType): bool
    {
        $exactMatch = BlacklistRuleDetail::CRITERIA_TYPE_EXACT_MATCH;
        $regex      = BlacklistRuleDetail::CRITERIA_TYPE_REGEX;
        $range      = BlacklistRuleDetail::CRITERIA_TYPE_RANGE;
        $subnetMask = BlacklistRuleDetail::CRITERIA_TYPE_SUBNET_MASK;
        $domain     = BlacklistRuleDetail::CRITERIA_TYPE_DOMAIN;
        $areaCode   = BlacklistRuleDetail::CRITERIA_TYPE_AREA_CODE;
        $multiple   = BlacklistRuleDetail::CRITERIA_TYPE_MULTIPLE;

        if ($type == BlacklistRuleDetail::RULE_TYPE_IP && ! in_array($criteriaType, [$exactMatch, $range, $regex, $subnetMask])) {
            return false;
        }

        if ($type == BlacklistRuleDetail::RULE_TYPE_EMAIL && ! in_array($criteriaType, [$exactMatch, $domain, $regex])) {
            return false;
        }

        if ($type == BlacklistRuleDetail::RULE_TYPE_PHONE && ! in_array($criteriaType, [$exactMatch, $areaCode, $regex])) {
            return false;
        }

        if ($type == BlacklistRuleDetail::RULE_TYPE_ADDRESS && ! in_array($criteriaType, [$exactMatch, $regex])) {
            return false;
        }

        if ($type == BlacklistRuleDetail::RULE_TYPE_API_PAYLOAD && ! in_array($criteriaType, [$exactMatch, $regex])) {
            return false;
        }

        if ($type == BlacklistRuleDetail::RULE_TYPE_BIN_NUMBER && ! in_array($criteriaType, [$exactMatch, $range, $multiple])) {
            return false;
        }

        if ($type == BlacklistRuleDetail::RULE_TYPE_CC_NUMBER && ! in_array($criteriaType, [$exactMatch, $regex])) {
            return false;
        }

        return true;
    }

    /**
     * Validate a blacklist rule detail with a pre-validated request.
     *
     * @param SaveRuleDetailRequest $request
     * @return void
     */
    public function validateRuleDetailsRequest(SaveRuleDetailRequest $request): void
    {
        if (! $this->isApplyToCorrect($request->applied_to)) {
            throw new \Exception('Invalid Rule Applied To');
        }

        if (! $this->isRuleTypeCorrect($request->type)) {
            throw new \Exception('Invalid Rule Type');
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_ADDRESS && ! $this->isAddressComponentTypeCorrect($request->component_type)) {
            throw new \Exception('Invalid Address Component Type');
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_IP && ! $this->isIPComponentTypeCorrect($request->component_type)) {
            throw new \Exception('Invalid Component Type');
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_DECLINE && 1 > (int) $request->decline_attempts) {
            throw new \Exception('Invalid Number of Attempts');
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_DECLINE && 1 > (int) $request->decline_duration) {
            throw new \Exception('Invalid Total Duration');
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_CHECKIN && $request->routing_num == '') {
            throw new \Exception('Invalid Routing Number');
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_CHECKIN && $request->account_num == '') {
            throw new \Exception('Invalid Account Number');
        }

        if (in_array($request->type, $this->criteriaBasedRuleTypes()) && ! $this->isCriteriaTypeCorrect($request->type, $request->criteria_type)) {
            throw new \Exception('Invalid Criteria Type');
        }

        if ($request->criteria_type == BlacklistRuleDetail::CRITERIA_TYPE_RANGE && ($request->range_min == '' || $request->range_max = '')) {
            throw new \Exception('Invalid Range Values');
        }

        $valuesInArr = $request->get('values');
        if ($request->criteria_type == BlacklistRuleDetail::CRITERIA_TYPE_MULTIPLE && ! is_array($valuesInArr)) {
            throw new \Exception('Invalid Bin Values');
        }

        $hasValidValues = (count($valuesInArr) === count(array_filter($valuesInArr, 'is_numeric')));

        if ($request->criteria_type == BlacklistRuleDetail::CRITERIA_TYPE_MULTIPLE && (empty($valuesInArr) || ! $hasValidValues)) {
            throw new \Exception('Invalid Bin Values');
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_IP && BlacklistRuleDetail::IP_COMPONENT_TYPE_GEO_LOCATION == $request->component_type && ! is_array($valuesInArr)) {
            throw new \Exception('Invalid Country Values');
        }
        if ($request->type == BlacklistRuleDetail::RULE_TYPE_IP && BlacklistRuleDetail::IP_COMPONENT_TYPE_GEO_LOCATION == $request->component_type && $valuesInArr['country'] == '') {
            throw new \Exception('Country is required');
        }
    }

    /**
     * Filter valid data to save.
     *
     * @param SaveRuleDetailRequest $request
     * @return array
     */
    public function getValidRuleDetailDataToSave(SaveRuleDetailRequest $request, int $detailId = 0): array
    {
        $validData = [
            'applied_to'       => $request->applied_to,
            'rule_id'          => $request->rule_id,
            'type'             => $request->type,
            'rule_status'      => $request->get('rule_status', 1),
            'status'           => $request->get('status', 1),
            'component_type'   => '',
            'criteria_type'    => '',
            'decline_attempts' => '',
            'decline_duration' => '',
            'routing_num'      => '',
            'account_num'      => '',
            'values'           => '',
            'range_min'        => '',
            'range_max'        => '',
        ];

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_ADDRESS || $request->type == BlacklistRuleDetail::RULE_TYPE_IP) {
            $validData['component_type'] = $request->component_type;
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_DECLINE) {
            $validData['decline_attempts'] = $request->decline_attempts;
            $validData['decline_duration'] = $request->decline_duration;
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_CHECKIN) {
            $validData['routing_num'] = $request->routing_num;
            $validData['account_num'] = $request->account_num;
        }

        if (in_array($request->type, $this->criteriaBasedRuleTypes())) {
            $validData['criteria_type'] = $request->criteria_type;

            if ($request->criteria_type != BlacklistRuleDetail::CRITERIA_TYPE_RANGE && $request->criteria_type != BlacklistRuleDetail::CRITERIA_TYPE_MULTIPLE) {
                $validData['values'] = $request->values;
            }
        }

        if (in_array($request->type, $this->criteriaBasedRuleTypes()) && $request->criteria_type == BlacklistRuleDetail::CRITERIA_TYPE_RANGE) {
            $validData['range_min'] = $request->get('range_min');
            $validData['range_max'] = $request->get('range_max');
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_IP && $request->component_type == BlacklistRuleDetail::IP_COMPONENT_TYPE_GEO_LOCATION) {
            $validData['values'] = json_encode($request->values);
        }

        if ($request->type == BlacklistRuleDetail::RULE_TYPE_CC_NUMBER && $request->criteria_type == BlacklistRuleDetail::CRITERIA_TYPE_EXACT_MATCH) {
            $validData['values'] = \payment_source::encrypt_credit_card($request->values);
        }

        if (0 < $detailId) {
            $validData['updated_by'] = get_current_user_id();
        }

        return $validData;
    }
}
