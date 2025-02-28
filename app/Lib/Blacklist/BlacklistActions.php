<?php

namespace App\Lib\Blacklist;

use App\Models\Blacklist\BlacklistRule;
use App\Models\Blacklist\BlacklistRuleDetail;
use App\Models\Blacklist\BlacklistedEntity;
use App\Models\Blacklist\BlacklistRulesHistory;
use App\Models\Blacklist\BlacklistBinNumber;
use App\Models\ConfigSetting;
use App\Models\Country;
use App\Models\Prospect;
use App\Models\Order;
use Carbon\Carbon;
use App\Lib\Blacklist\ModuleRequests\BlacklistDetailRequest;
use DB;

/**
 * Class BlacklistActions
 *
 * @package App\Lib\Blacklist
 */
class BlacklistActions
{
    public           $entityType;

    public           $entityDetails;

    public           $blacklistData;

    public           $blacklistObservedData;

    public           $apiPayload;

    public           $blacklistComment;

    public           $blSource;

    protected static $isV2Enabled = null;

    public function __construct($entityType, $entityDetails = null, $blacklistData = [], $blSource = 'System')
    {
        $this->entityType       = $entityType;
        $this->entityDetails    = $entityDetails;
        $this->blSource         = $blSource;
        $this->blacklistComment = '';
        $this->mergeEntityData($this->entityType, $this->entityDetails, $blacklistData);
        $this->blacklistObservedData = [
            'criteria_value'   => '',
            'decline_duration' => '',
            'decline_attempts' => '',
            'routing_num'      => '',
            'account_num'      => '',
        ];
    }

    /**
     * Merge the blacklist data with given entity data
     *
     * @return void
     */
    public function mergeEntityData($entityType, $entityDetails = null, $blacklistData = []): void
    {
        $eBlacklistData = [];
        $data           = [
            'entity_id'        => '',
            'email'            => '',
            'ip_address'       => '',
            'cc_num'           => '',
            'phone'            => '',
            'address_1'        => '',
            'address_2'        => '',
            'city'             => '',
            'state'            => '',
            'country'          => '',
            'zipcode'          => '',
            'api_payload'      => [],
            'cc_bin'           => '',
            'decline_attempts' => 0,
            'decline_period'   => 0,
            'account_num'      => '',
            'routing_num'      => '',
        ];

        if (! empty($entityDetails)) {
            if ($entityType == BlacklistRuleDetail::APPLY_TO_ORDER) {
                $shipping_country_iso2 = isset($entityDetails->shipping_country_iso2) ? $entityDetails->shipping_country_iso2 : Country::where('countries_id', $entityDetails->delivery_country)->first()->iso_2;
                $eBlacklistData       = [
                    'entity_id'   => $entityDetails->orders_id,
                    'email'       => $entityDetails->customers_email_address,
                    'ip_address'  => $entityDetails->ip_address,
                    'cc_num'      => \payment_source::decrypt_credit_card($entityDetails->charge_c),
                    'phone'       => $entityDetails->customers_telephone,
                    'address_1'   => $entityDetails->delivery_street_address,
                    'address_2'   => $entityDetails->delivery_suburb,
                    'city'        => $entityDetails->delivery_city,
                    'state'       => $entityDetails->delivery_state,
                    'country'     => $shipping_country_iso2,
                    'zipcode'     => $entityDetails->delivery_postcode,
                    'account_num' => $entityDetails->checking_routing_number,
                    'routing_num' => $entityDetails->checking_account_number,
                ];
            }
            if ($entityType == BlacklistRuleDetail::APPLY_TO_PROSPECT) {
                $eBlacklistData = [
                    'entity_id'  => $entityDetails->prospects_id,
                    'email'      => $entityDetails->pEmail,
                    'ip_address' => $entityDetails->pIPAddress,
                    'phone'      => $entityDetails->pPhone,
                    'address_1'  => $entityDetails->pAddress,
                    'address_2'  => $entityDetails->pAddress2,
                    'city'       => $entityDetails->pCity,
                    'state'      => $entityDetails->pState,
                    'country'    => $entityDetails->pCountry,
                    'zipcode'    => $entityDetails->pZip,
                ];
            }
        }

        $data = array_merge($data, $blacklistData);

        $this->blacklistData = array_merge($data, $eBlacklistData);

        if (isset($blacklistData['cc_num']) && ! empty($blacklistData['cc_num']) && strlen($blacklistData['cc_num']) >= 6) {
            $this->blacklistData['cc_bin'] = substr($blacklistData['cc_num'], 0, 6);
        }
    }

    /**
     * Check whether an IP address is within a given range
     *
     * @return bool
     */
    public static function isIPBetweenRange($ip, $startIP, $endIP): bool
    {
        if (inet_pton($ip) >= inet_pton($startIP) && inet_pton($ip) <= inet_pton($endIP)) {
            return true;
        }

        return false;
    }

    /**
     * Check if blacklist V2 is enabled
     *
     * @return bool
     */
    public static function checkBlacklistV2Enabled(): bool
    {
        if (is_null(self::$isV2Enabled)) {
            $config            = ConfigSetting::key(ConfigSetting::ENABLE_BLACKLIST_V2_SETTING)->first();
            self::$isV2Enabled = ($config && $config->value == '1') ? true : false;
        }

        return self::$isV2Enabled;
    }

    /**
     * Check whether a provided blacklist rule applies to certain blacklisting criteria
     *
     * @return bool
     */
    public function checkCriteriaBlacklistStatus(
        BlacklistRuleDetail $rule,
        $value,
        $checking = false,
        $checkingType = 'acc'
    ): bool {
        $blStatus     = false;
        $criteriaType = $rule->criteria_type;

        if ($checking) {
            if ($checkingType == 'acc') {
                if ($rule->account_num == $value) {
                    $blStatus = true;
                }
            } else {
                if ($rule->routing_num == $value) {
                    $blStatus = true;
                }
            }
        } else {
            switch ($criteriaType) {
                case BlacklistRuleDetail::CRITERIA_TYPE_EXACT_MATCH:
                    $ruleVal  = (string) $rule->values;
                    $matchVal = (string) $value;
                    if ($rule->type == BlacklistRuleDetail::RULE_TYPE_CC_NUMBER) {
                        $ruleVal = \payment_source::decrypt_credit_card($ruleVal);
                    }
                    if (strlen($ruleVal) == strlen($matchVal) && $ruleVal == $matchVal) {
                        $blStatus = true;
                    }
                    break;
                case BlacklistRuleDetail::CRITERIA_TYPE_REGEX:
                    if (preg_match($rule->values, $value)) {
                        $blStatus = true;
                    }
                    break;
                case BlacklistRuleDetail::CRITERIA_TYPE_AREA_CODE:
                    $value = preg_replace('/[^0-9]/', '', $value);
                    if (substr($value, 0, strlen($rule->values)) == $rule->values) {
                        $blStatus = true;
                    }
                    break;
                case BlacklistRuleDetail::CRITERIA_TYPE_DOMAIN:
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $parts  = explode('@', $value);
                        $domain = array_pop($parts);
                        if ($rule->values == $domain) {
                            $blStatus = true;
                        }
                    }
                    break;
                case BlacklistRuleDetail::CRITERIA_TYPE_RANGE:
                    if ($rule->type == BlacklistRuleDetail::RULE_TYPE_IP) {
                        if (Self::isIPBetweenRange($value, $rule->range_min, $rule->range_max)) {
                            $blStatus = true;
                        }
                    }
                    if ($rule->type == BlacklistRuleDetail::RULE_TYPE_BIN_NUMBER) {
                        if ($value >= $rule->range_min && $value <= $rule->range_max) {
                            $blStatus = true;
                        }
                    }
                    break;
                case BlacklistRuleDetail::CRITERIA_TYPE_SUBNET_MASK:
                    if ($rule->component_type == BlacklistRuleDetail::IP_COMPONENT_TYPE_IPV4) {
                        $subnetMask = explode('/', $rule->values);
                        $subnet     = (isset($subnetMask[0])) ? $subnetMask[0] : '';
                        $mask       = (isset($subnetMask[1])) ? $subnetMask[1] : -1;
                        if ($mask <= 0) {
                            $blStatus = false;
                        } else {
                            $ip_bin_string  = sprintf("%032b", ip2long($value));
                            $net_bin_string = sprintf("%032b", ip2long($subnet));
                            $blStatus       = (substr_compare($ip_bin_string, $net_bin_string, 0, $mask) === 0);
                        }
                    }
                    if ($rule->component_type == BlacklistRuleDetail::IP_COMPONENT_TYPE_IPV6) {
                        $subnetMask = explode('/', $rule->values);
                        $subnet     = (isset($subnetMask[0])) ? $subnetMask[0] : '';
                        $mask       = (isset($subnetMask[1])) ? $subnetMask[1] : -1;

                        if ($mask <= 0) {
                            $blStatus = false;
                        } else {
                            $subnet  = inet_pton($subnet);
                            $ip      = inet_pton($value);
                            $binMask = str_repeat("f", $mask / 4);
                            switch ($mask % 4) {
                                case 0:
                                    break;
                                case 1:
                                    $binMask .= "8";
                                    break;
                                case 2:
                                    $binMask .= "c";
                                    break;
                                case 3:
                                    $binMask .= "e";
                                    break;
                            }
                            $binMask = str_pad($binMask, 32, '0');
                            $binMask = pack("H*", $binMask);

                            $blStatus = ($ip & $binMask) == $subnet;
                        }
                    }
                    break;
                case BlacklistRuleDetail::CRITERIA_TYPE_MULTIPLE:
                    if ($rule->type == BlacklistRuleDetail::RULE_TYPE_BIN_NUMBER) {
                        $blBinNum = BlacklistBinNumber::where([
                            'rule_detail_id' => $rule->id,
                            'value'          => $value,
                        ])->first();
                        if ($blBinNum) {
                            $blStatus = true;
                        }
                    }
                    break;
            }
        }

        return $blStatus;
    }

    /**
     * Check if address component is blacklisted
     *
     * @return bool
     */
    public function checkAddressBlacklistStatus(BlacklistRuleDetail $rule): bool
    {
        switch ($rule->component_type) {
            case BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_ADDRESS_1:
                if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['address_1'])) {
                    $this->blacklistObservedData['criteria_value'] = $this->blacklistData['address_1'];
                    $this->blacklistComment                        = 'An address 1 with value "'.$this->blacklistData['address_1'].'" was blacklisted based on rule\'s criteria value "'.$rule->values.'"';

                    return true;
                }
                break;
            case BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_ADDRESS_2:
                if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['address_2'])) {
                    $this->blacklistObservedData['criteria_value'] = $this->blacklistData['address_2'];
                    $this->blacklistComment                        = 'An address 2 with value "'.$this->blacklistData['address_2'].'" was blacklisted based on rule\'s criteria value "'.$rule->values.'"';

                    return true;
                }
                break;
            case BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_CITY:
                if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['city'])) {
                    $this->blacklistObservedData['criteria_value'] = $this->blacklistData['city'];
                    $this->blacklistComment                        = 'A city with value "'.$this->blacklistData['city'].'" was blacklisted based on rule\'s criteria value "'.$rule->values.'"';

                    return true;
                }
                break;
            case BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_COUNTRY:
                if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['country'])) {
                    $this->blacklistObservedData['criteria_value'] = $this->blacklistData['country'];
                    $this->blacklistComment                        = 'A country with value "'.$this->blacklistData['country'].'" was blacklisted based on rule\'s criteria value "'.$rule->values.'"';

                    return true;
                }
                break;
            case BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_STATE:
                if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['state'])) {
                    $this->blacklistObservedData['criteria_value'] = $this->blacklistData['state'];
                    $this->blacklistComment                        = 'A state with value "'.$this->blacklistData['state'].'" was blacklisted based on rule\'s criteria value "'.$rule->values.'"';

                    return true;
                }
                break;
            case BlacklistRuleDetail::ADDRESS_COMPONENT_TYPE_ZIPCODE:
                if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['zipcode'])) {
                    $this->blacklistObservedData['criteria_value'] = $this->blacklistData['zipcode'];
                    $this->blacklistComment                        = 'A zipcode with value "'.$this->blacklistData['zipcode'].'" was blacklisted based on rule\'s criteria value "'.$rule->values.'"';

                    return true;
                }
                break;
            default:
                return false;
        }

        return false;
    }

    /**
     * Check if address IP Address is blacklisted
     *
     * @return bool
     */
    public function checkIpBlacklistStatus(BlacklistRuleDetail $rule): bool
    {
        switch ($rule->component_type) {
            case BlacklistRuleDetail::IP_COMPONENT_TYPE_IPV4:
            case BlacklistRuleDetail::IP_COMPONENT_TYPE_IPV6:
                if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['ip_address'])) {
                    $this->blacklistObservedData['criteria_value'] = $this->blacklistData['ip_address'];
                    $this->blacklistComment                        = 'An IP Address with value "'.$this->blacklistData['ip_address'].'" was blacklisted';

                    return true;
                }
                break;
            case BlacklistRuleDetail::IP_COMPONENT_TYPE_GEO_LOCATION:
                if (! empty($this->blacklistData['ip_address'])) {
                    return $this->checkGeoLocationBlacklistStatus($rule, $this->blacklistData['ip_address']);
                }
                break;
            default:
                return false;
        }

        return false;
    }

    /**
     * Check if address IP Address geo location is blacklisted
     *
     * @return bool
     */
    public function checkGeoLocationBlacklistStatus(BlacklistRuleDetail $rule, string $ip): bool
    {
        $return           = false;
        $geoIp            = new \geo_location();
        $IpAddressDetails = $geoIp->get($ip);
        $iso2             = $geoIp->get_country_iso_2();

        $pattern = '/([^,]+)\,\s*([A-Z]{2})\s+([0-9a-zA-Z\-]*)\s*(.*)\s*$/m';
        preg_match_all($pattern, $IpAddressDetails, $matches, PREG_SET_ORDER, 0);

        $blackListDetailsValueArr = json_decode($rule->values, true);

        if (! empty($matches)) {
            $resultArr   = $matches[0];
            $resultArr[] = $iso2;
            if ($blackListDetailsValueArr['country'] != end($resultArr)) {
                return false;
            } else {
                $return = true;
            }
            if (! empty($blackListDetailsValueArr['city']) && strtolower($blackListDetailsValueArr['city']) != strtolower($resultArr[1])) {
                return false;
            } else {
                $return = true;
            }
            if (! empty($blackListDetailsValueArr['state']) && strtolower($blackListDetailsValueArr['state']) != strtolower($resultArr[2])) {
                return false;
            } else {
                $return = true;
            }
            if (! empty($blackListDetailsValueArr['zip']) && strtolower($blackListDetailsValueArr['zip']) != strtolower($resultArr[3])) {
                return false;
            } else {
                $return = true;
            }
        }
        if ($return) {
            $this->blacklistObservedData['criteria_value'] = $ip;
            $this->blacklistComment                        = 'An IP Geo-location with value "'.$IpAddressDetails.' ('.$ip.')" was blacklisted';
        }

        return $return;
    }

    /**
     * Check if Email is blacklisted
     *
     * @return bool
     */
    public function checkEmailBlacklistStatus(BlacklistRuleDetail $rule): bool
    {
        if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['email'])) {
            $this->blacklistObservedData['criteria_value'] = $this->blacklistData['email'];
            $this->blacklistComment                        = 'An email with value "'.$this->blacklistData['email'].'" was blacklisted based on rule\'s criteria value "'.$rule->values.'"';

            return true;
        }

        return false;
    }

    /**
     * Check if phone is blacklisted
     *
     * @return bool
     */
    public function checkPhoneBlacklistStatus(BlacklistRuleDetail $rule): bool
    {
        if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['phone'])) {
            $this->blacklistObservedData['criteria_value'] = $this->blacklistData['phone'];
            $this->blacklistComment                        = 'A phone with value "'.$this->blacklistData['phone'].'" was blacklisted based on rule\'s criteria value "'.$rule->values.'"';

            return true;
        }

        return false;
    }

    /**
     * Check API Payload value is blacklisted
     *
     * @return bool
     */
    public function checkPayloadBlacklistStatus(BlacklistRuleDetail $rule): bool
    {
        foreach ($this->blacklistData['api_payload'] as $key => $val) {
            if ($this->checkCriteriaBlacklistStatus($rule, $val)) {
                $this->blacklistObservedData['criteria_value'] = $val;
                $this->blacklistComment                        = 'A Request Payload with value "'.$val.'" was blacklisted';

                return true;
            }
        }

        return false;
    }

    /**
     * Check Credit Card Number is blacklisted
     *
     * @return bool
     */
    public function checkCCNumBlacklistStatus(BlacklistRuleDetail $rule): bool
    {
        if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['cc_num'])) {
            $this->blacklistObservedData['criteria_value'] = $this->blacklistData['cc_num'];
            $this->blacklistComment                        = 'An order with CC number "'.$this->blacklistData['cc_num'].'" was blacklisted';

            return true;
        }

        return false;
    }

    /**
     * Check Credit Card BIN value is blacklisted
     *
     * @return bool
     */
    public function checkCCBinBlacklistStatus(BlacklistRuleDetail $rule): bool
    {
        if (! empty($this->blacklistData['cc_bin']) && $this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['cc_bin'])) {
            $this->blacklistObservedData['criteria_value'] = $this->blacklistData['cc_bin'];
            $this->blacklistComment                        = 'A CC BIN number with value "'.$this->blacklistData['cc_bin'].'" was blacklisted';

            return true;
        }

        return false;
    }

    /**
     * Check Checking is blacklisted
     *
     * @return bool
     */
    public function checkCheckingSepaBlacklistStatus(BlacklistRuleDetail $rule): bool
    {
        if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['account_num'], true)) {
            $this->blacklistObservedData['account_num'] = $this->blacklistData['account_num'];
            $this->blacklistComment                     = 'An account number with value "'.$this->blacklistData['account_num'].'" was blacklisted based on rule\'s criteria value "'.$rule->account_num.'"';

            return true;
        }
        if ($this->checkCriteriaBlacklistStatus($rule, $this->blacklistData['routing_num'], true, 'routing')) {
            $this->blacklistObservedData['routing_num'] = $this->blacklistData['routing_num'];
            $this->blacklistComment                     = 'An routing number with value "'.$this->blacklistData['routing_num'].'" was blacklisted based on rule\'s criteria value "'.$rule->routing_num.'"';

            return true;
        }

        return false;
    }

    /**
     * Create system generated blacklist rule
     *
     * @return BlacklistRuleDetail|null
     */
    public static function createCustomRule(
        $entityType,
        $ruleType,
        $value,
        $ruleId = 0,
        $source = 'System'
    ): ?BlacklistRuleDetail {
        $existingRule = BlacklistRuleDetail::where([
            'applied_to'    => $entityType,
            'type'          => $ruleType,
            'criteria_type' => BlacklistRuleDetail::CRITERIA_TYPE_EXACT_MATCH,
            'values'        => $value,
        ])->first();

        if ($existingRule) {
            return $existingRule;
        }

        if(!empty($ruleId)) {
            $newRule = BlacklistRule::find($ruleId);
        } else {
            $newRule = BlacklistRule::create([
                'name'        => 'Auto generated',
                'description' => 'Rule auto generated from '.$source,
                'status'      => 1,
            ]);
        }

        if ($newRule) {
            $ruleDetail = BlacklistRuleDetail::create([
                'applied_to'       => $entityType,
                'rule_id'          => $newRule->id,
                'type'             => $ruleType,
                'rule_status'      => 1,
                'status'           => 1,
                'component_type'   => '',
                'criteria_type'    => BlacklistRuleDetail::CRITERIA_TYPE_EXACT_MATCH,
                'decline_attempts' => '',
                'decline_duration' => '',
                'routing_num'      => '',
                'account_num'      => '',
                'values'           => $value,
                'range_min'        => '',
                'range_max'        => '',
            ]);

            if ($ruleDetail) {
                return $ruleDetail;
            }
        }

        return null;
    }

    /**
     * Check Decline Attempts Number is blacklisted
     *
     * @return bool
     */
    public function checkDeclineBlacklistStatus(BlacklistRuleDetail $rule): bool
    {
        $declinedOrderCount = self::getDeclinedOrderCount($this->blacklistData['email'], $rule->decline_duration);

        if (! empty($rule->decline_attempts) && $declinedOrderCount >= $rule->decline_attempts) {
            $ruleDetail = self::createCustomRule(BlacklistRuleDetail::APPLY_TO_ORDER, BlacklistRuleDetail::RULE_TYPE_EMAIL, $this->blacklistData['email'], 0, $this->blSource);

            if ($ruleDetail && ! empty($this->blacklistData['cc_num'])) {
                self::createCustomRule(BlacklistRuleDetail::APPLY_TO_ORDER, BlacklistRuleDetail::RULE_TYPE_CC_NUMBER, $this->blacklistData['cc_num'], $ruleDetail->rule_id, $this->blSource);
            }
            $this->blacklistComment = 'An order with "'.$rule->decline_attempts.'" decline attempts within "'.$rule->decline_duration.'" seconds was blacklisted';

            return true;
        }

        return false;
    }

    /**
     * Check if a provided entity is blacklisted
     *
     * @return bool
     */
    public static function checkEntityBlacklisted($entityType, $entityDetail): bool
    {
        $entityId = self::getEntityId($entityType, $entityDetail);
        if ($entityType == BlacklistRuleDetail::APPLY_TO_ORDER && ! empty($entityDetail->blacklist_id)) {
            return true;
        }

        if (BlacklistedEntity::where(['entity_type' => $entityType, 'entity_id' => $entityId])->count() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Check if a given order is blacklisted
     *
     * @return bool
     */
    public function isEntityBlacklisted(bool $checkOnly = false): bool
    {
        if (! empty($this->entityDetails) && self::checkEntityBlacklisted($this->entityType, $this->entityDetails)) {
            return true;
        }

        if ($this->entityType == BlacklistRuleDetail::APPLY_TO_ORDER) {
            if (! empty($this->entityDetails) && self::isTestOrder($this->entityDetails)) {
                return false;
            }

            return $this->blacklistOrder($checkOnly);
        }
        if ($this->entityType == BlacklistRuleDetail::APPLY_TO_PROSPECT) {
            return $this->blacklistProspect($checkOnly);
        }

        return false;
    }

    /**
     * Blacklist an Order
     *
     * @return bool
     */
    public function blacklistOrder(bool $checkOnly = false)
    {
        $blacklistRules = BlacklistRuleDetail::where([
            'status'      => 1,
            'rule_status' => 1,
            'applied_to'  => BlacklistRuleDetail::APPLY_TO_ORDER,
        ])->get();
        $status         = false;
        foreach ($blacklistRules as $rule) {
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_EMAIL) {
                if ($status = $this->checkEmailBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_ADDRESS) {
                if ($status = $this->checkAddressBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_IP) {
                if ($status = $this->checkIpBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_PHONE) {
                if ($status = $this->checkPhoneBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_API_PAYLOAD) {
                if ($status = $this->checkPayloadBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_CC_NUMBER) {
                if ($status = $this->checkCCNumBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_BIN_NUMBER) {
                if ($status = $this->checkCCBinBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_CHECKIN) {
                if ($status = $this->checkCheckingSepaBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_DECLINE) {
                if ($status = $this->checkDeclineBlacklistStatus($rule)) {
                    break;
                }
            }
        }
        if ($status && ! $checkOnly) {
            if (! empty($this->entityDetails)) {
                $this->markOrderBlacklisted($rule);
            }
            $this->saveBlacklistHistory($rule, BlacklistRulesHistory::ACTION_TYPE_ORDER_BLACKLISTED);
        }

        return $status;
    }

    /**
     * Mark an order as blacklisted
     *
     * @return void
     */
    public function markOrderBlacklisted(BlacklistRuleDetail $rule): void
    {
        $entityId = self::getEntityId($this->entityType, $this->entityDetails);
        $this->markEntityBlacklisted($rule);
        self::stopRecurring($entityId, 999998);
        $type = 'history-note-blacklisted';
        $msg  = 'Customer order was blacklisted.';
        $uid  = current_user();
        OrderTrackingBatch($uid, $type, $msg, [$this->entityDetails->orders_id]);
    }

    /**
     * Mark an entity as blacklisted
     *
     * @return BlacklistedEntity
     */
    public function markEntityBlacklisted(BlacklistRuleDetail $rule)
    {
        $entityId = self::getEntityId($this->entityType, $this->entityDetails);

        return BlacklistedEntity::create([
            'rule_detail_id' => $rule->id,
            'entity_type'    => $this->entityType,
            'entity_id'      => $entityId,
        ]);
    }

    /**
     * Check if an order is a test order
     *
     * @return bool
     */
    public static function isTestOrder($order): bool
    {
        $list                     = [];
        $list['updateOrdersFlag'] = false;
        $list['updateCountFlag']  = false;

        /**
         * Checking or credit card
         */
        if (! empty($order->charge_c)) {
            $list['ccNumber'] = \payment_source::decrypt_credit_card($order->charge_c);
        } elseif (! empty($order->checking_account_number) && ! empty($order->checking_routing_number)) {
            $list['checkAccountNumber'] = $order->checking_account_number;
            $list['checkRoutingNumber'] = $order->checking_routing_number;
        }

        if (isTestCC($list) !== false) {
            return true;
        }
        if ($order->is_test_cc) {
            return true;
        }

        return false;
    }

    /**
     * Forcefully blacklist an entity
     *
     * @return bool
     */
    public function forceBlacklistEntity()
    {
        if ($this->entityType == BlacklistRuleDetail::APPLY_TO_ORDER) {
            if (self::checkEntityBlacklisted($this->entityType, $this->entityDetails)) {
                return -1;
            }
            if (self::isTestOrder($this->entityDetails)) {
                return -2;
            } else {
                if ($this->isEntityBlacklisted()) {
                    return true;
                }
                $ruleDetail = self::createCustomRule(BlacklistRuleDetail::APPLY_TO_ORDER, BlacklistRuleDetail::RULE_TYPE_EMAIL, $this->blacklistData['email'], 0, $this->blSource);

                if ($ruleDetail && ! empty($this->blacklistData['cc_num'])) {
                    self::createCustomRule(BlacklistRuleDetail::APPLY_TO_ORDER, BlacklistRuleDetail::RULE_TYPE_CC_NUMBER, $this->blacklistData['cc_num'], $ruleDetail->rule_id, $this->blSource);
                }

                if ($this->isEntityBlacklisted()) {
                    return true;
                }
            }

            return false;
        }
        if ($this->entityType == BlacklistRuleDetail::APPLY_TO_PROSPECT) {
            if ($this->isEntityBlacklisted()) {
                return true;
            }
            self::createCustomRule(BlacklistRuleDetail::APPLY_TO_PROSPECT, BlacklistRuleDetail::RULE_TYPE_EMAIL, $this->blacklistData['email'], 0, $this->blSource);

            if ($this->isEntityBlacklisted()) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Blacklist a Prospect
     *
     * @return bool
     */
    public function blacklistProspect(bool $checkOnly = false)
    {
        $blacklistRules = BlacklistRuleDetail::where([
            'status'      => 1,
            'rule_status' => 1,
            'applied_to'  => BlacklistRuleDetail::APPLY_TO_PROSPECT,
        ])->get();
        $status         = false;
        foreach ($blacklistRules as $rule) {
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_EMAIL) {
                if ($status = $this->checkEmailBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_ADDRESS) {
                if ($status = $this->checkAddressBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_IP) {
                if ($status = $this->checkIpBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_PHONE) {
                if ($status = $this->checkPhoneBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_API_PAYLOAD) {
                if ($status = $this->checkPayloadBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_CC_NUMBER) {
                if ($status = $this->checkCCNumBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_BIN_NUMBER) {
                if ($status = $this->checkCCBinBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_CHECKIN) {
                if ($status = $this->checkCheckingSepaBlacklistStatus($rule)) {
                    break;
                }
            }
            if ($rule->type == BlacklistRuleDetail::RULE_TYPE_DECLINE) {
                if ($status = $this->checkDeclineBlacklistStatus($rule)) {
                    break;
                }
            }
        }
        if ($status && ! $checkOnly) {
            if (! empty($this->entityDetails)) {
                $this->markProspectBlacklisted($rule);
            }
            $this->saveBlacklistHistory($rule, BlacklistRulesHistory::ACTION_TYPE_PROSPECT_BLACKLISTED);
        }

        return $status;
    }

    /**
     * Get the entity ID from entity details
     *
     * @return int
     */
    public static function getEntityId($entityType, $entityDetail)
    {
        $entityId = 0;
        if (! empty($entityDetail)) {
            if ($entityType == BlacklistRuleDetail::APPLY_TO_ORDER) {
                $entityId = $entityDetail->orders_id;
            }

            if ($entityType == BlacklistRuleDetail::APPLY_TO_PROSPECT) {
                $entityId = $entityDetail->prospects_id;
            }
        }

        return $entityId;
    }

    public function markProspectBlacklisted(BlacklistRuleDetail $rule)
    {
        $this->markEntityBlacklisted($rule);
    }

    public static function getDeclinedOrderCount($email, $duration)
    {
        return Order::where([
            'customers_email_address' => $email,
            'orders_status'           => 7,
        ])->where('t_stamp', '>=', Carbon::now()->subSeconds($duration))->count();
    }

    /**
     * Stop recurring for blacklisted order
     *
     * @return bool
     */
    public static function stopRecurring($orderId, $adminId)
    {
        $order = Order::find($orderId);
        if ($order) {
            if ($order->is_recurring == 1) {
                OrderHistory($orderId, $adminId, 'history-note-blacklist-on-hold', 'Order was placed on hold because it was black listed.', $order->campaign_order_id);
                cancelAllSubscriptions($orderId, $adminId, \App\Models\SubscriptionHoldType::USER);
                commonProviderUpdateOrder($orderId, 'blacklist');
            }
        }
    }

    /**
     * Save blacklist history note
     *
     * @return bool
     */
    public function saveBlacklistHistory(BlacklistRuleDetail $rule, $actionType)
    {
        $entityId       = self::getEntityId($this->entityType, $this->entityDetails);
        $criteria_value = $rule->values;
        if ($rule->criteria_type == BlacklistRuleDetail::CRITERIA_TYPE_RANGE) {
            $criteria_value = $rule->range_min.'-'.$rule->range_max;
        }

        return BlacklistRulesHistory::create([
            'rule_detail_id'            => $rule->id,
            'type'                      => $rule->type,
            'entity_type'               => $this->entityType,
            'entity_id'                 => $entityId,
            'criteria_type'             => $rule->criteria_type,
            'component_type'            => $rule->component_type,
            'criteria_value'            => $criteria_value,
            'observed_value'            => $this->blacklistObservedData['criteria_value'],
            'decline_duration'          => $rule->decline_duration,
            'decline_attempts'          => $rule->decline_attempts,
            'observed_decline_duration' => $this->blacklistObservedData['decline_duration'],
            'observed_decline_attempts' => $this->blacklistObservedData['decline_attempts'],
            'routing_num'               => $rule->routing_num,
            'account_num'               => $rule->account_num,
            'observed_routing_num'      => $this->blacklistObservedData['routing_num'],
            'observed_account_num'      => $this->blacklistObservedData['account_num'],
            'action_type'               => $actionType,
            'comments'                  => $this->blacklistComment,
        ]);
    }

    /**
     * delete blacklisted_entities for orders and prospects after rule/rule details deleted
     *
     */
    public static function deleteBlacklistRuleDetails(array $ruleDetailIds)
    {
        if (! empty($ruleDetailIds)) {
            $blacklistedEntities = BlacklistedEntity::whereIn('rule_detail_id', $ruleDetailIds)->get();
            foreach ($blacklistedEntities as $blacklistedEntity) {
                if ($blacklistedEntity->entity_type == BlacklistRuleDetail::APPLY_TO_ORDER) {
                    if ($order = Order::find($blacklistedEntity->entity_id)) {
                        $order->addHistoryNote('history-note-blacklist-off', "Blacklist Removed");
                        BlacklistRulesHistory::create([
                            'rule_detail_id' => $blacklistedEntity->rule_detail_id,
                            'entity_type'    => $blacklistedEntity->entity_type,
                            'entity_id'      => $blacklistedEntity->entity_id,
                            'action_type'    => BlacklistRulesHistory::ACTION_TYPE_ORDER_UNBLACKLISTED,
                            'comments'       => "Blacklist Removed from Order ID ({$blacklistedEntity->entity_id})",
                        ]);
                        $blacklistedEntity->delete();
                    }
                }

                if ($blacklistedEntity->entity_type == BlacklistRuleDetail::APPLY_TO_PROSPECT) {
                    if ($prospect = Prospect::find($blacklistedEntity->entity_id)) {
                        BlacklistRulesHistory::create([
                            'rule_detail_id' => $blacklistedEntity->rule_detail_id,
                            'entity_type'    => $blacklistedEntity->entity_type,
                            'entity_id'      => $blacklistedEntity->entity_id,
                            'action_type'    => BlacklistRulesHistory::ACTION_TYPE_PROSPECT_UNBLACKLISTED,
                            'comments'       => "Blacklist Removed from Prospect ID ({$blacklistedEntity->entity_id})",
                        ]);
                        $blacklistedEntity->delete();
                    }
                }
            }
        }
    }

    /**
     * Manually unblacklist an order
     *
     */
    public static function unblacklistOrder($orderId): bool
    {
        $order = Order::find($orderId);

        if ($order) {
            if (! self::checkEntityBlacklisted(BlacklistRuleDetail::APPLY_TO_ORDER, $order)) {
                return true;
            } else {
                $blacklistHandler = new BlacklistHandler();

                $existingBlacklist = BlacklistedEntity::where([
                    'entity_id'   => $order->orders_id,
                    'entity_type' => BlacklistRuleDetail::APPLY_TO_ORDER,
                ])->first();

                $otherRules = DB::table('blacklist_rule_details')->where(function ($query) use ($order) {
                    $query->where('type', BlacklistRuleDetail::RULE_TYPE_EMAIL);
                    $query->where('applied_to', BlacklistRuleDetail::APPLY_TO_ORDER);
                    $query->where('criteria_type', BlacklistRuleDetail::CRITERIA_TYPE_EXACT_MATCH);
                    $query->where('values', $order->customers_email_address);
                    $query->whereNull('deleted_at');
                });

                if (! empty($order->charge_c)) {
                    $otherRules = $otherRules->orWhere(function ($query) use ($order) {
                        $query->where('type', BlacklistRuleDetail::RULE_TYPE_CC_NUMBER);
                        $query->where('applied_to', BlacklistRuleDetail::APPLY_TO_ORDER);
                        $query->where('criteria_type', BlacklistRuleDetail::CRITERIA_TYPE_EXACT_MATCH);
                        $query->where('values', \payment_source::decrypt_credit_card($order->charge_c));
                        $query->whereNull('deleted_at');
                    });
                }

                if ($existingBlacklist) {
                    $otherRules = $otherRules->orWhere(function ($query) use ($existingBlacklist) {
                        $query->where('id', $existingBlacklist->rule_detail_id);
                        $query->whereNull('deleted_at');
                    });
                }

                $otherRules = $otherRules->get();

                try {
                    if (! empty($otherRules)) {
                        foreach ($otherRules as $blRule) {
                            $blacklistHandler->destroyRuleDetails(new BlacklistDetailRequest(['id' => $blRule->id]));
                        }
                    }
                } catch (\Exception $e) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
