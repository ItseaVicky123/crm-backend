<?php

namespace App\Models;

use App\Models\ValueAddTransaction;

/**
 * Class ChargebackValueAddTransaction
 * @package App\Models
 */
class ChargebackValueAddTransaction extends ValueAddTransaction
{
    // outcomes
    const OUTCOME_UNDEFINED                           = 22;
    const OUTCOME_UNRESPONSIVE                        = 23;
    const OUTCOME_STORE_ALERT_SUCCESS                 = 24;
    const OUTCOME_STORE_ALERT_FAIL                    = 25;
    const OUTCOME_CONFIRM_ALERT_SUCCESS               = 26;
    const OUTCOME_CONFIRM_ALERT_FAIL                  = 27;
    const OUTCOME_UPDATE_ALERT_SUCCESS                = 28;
    const OUTCOME_UPDATE_ALERT_FAIL                   = 29;
    const OUTCOME_STORE_REPRESENTMENT_SUCCESS         = 30;
    const OUTCOME_STORE_REPRESENTMENT_FAIL            = 31;
    const OUTCOME_CONFIRM_REPRESENTMENT_SUCCESS       = 32;
    const OUTCOME_CONFIRM_REPRESENTMENT_FAIL          = 33;
    const OUTCOME_UPDATE_REPRESENTMENT_SUCCESS        = 34;
    const OUTCOME_UPDATE_REPRESENTMENT_FAIL           = 35;
    const OUTCOME_MANUAL_HANDLE_ALERT_SUCCESS         = 36;
    const OUTCOME_MANUAL_HANDLE_ALERT_FAIL            = 37;
    const OUTCOME_MANUAL_HANDLE_REPRESENTMENT_SUCCESS = 38;
    const OUTCOME_MANUAL_HANDLE_REPRESENTMENT_FAIL    = 39;
    const OUTCOME_FLAG_CHARGEBACK_SUCCESS             = 40;
    const OUTCOME_FLAG_CHARGEBACK_FAIL                = 41;
    const OUTCOME_STOP_SUBSCRIPTION_SUCCESS           = 42;
    const OUTCOME_STOP_SUBSCRIPTION_FAIL              = 43;
    const OUTCOME_REFUND_SUCCESS                      = 44;
    const OUTCOME_REFUND_FAIL                         = 45;
    const OUTCOME_REFUND_PARTIAL_SUCCESS              = 46;
    const OUTCOME_REFUND_PARTIAL_FAIL                 = 47;
    const OUTCOME_VOID_SUCCESS                        = 48;
    const OUTCOME_VOID_FAIL                           = 49;
    const OUTCOME_BLACKLIST_SUCCESS                   = 50;
    const OUTCOME_BLACKLIST_FAIL                      = 51;
    // actions
    const ACTION_UNDEFINED                            = 'undefined';
    const ACTION_STORE_ALERT                          = 'store_alert';
    const ACTION_CONFIRM_ALERT                        = 'confirm_alert';
    const ACTION_UPDATE_ALERT                         = 'update_alert';
    const ACTION_STORE_REPRESENTMENT                  = 'store_representment';
    const ACTION_CONFIRM_REPRESENTMENT                = 'confirm_representment';
    const ACTION_UPDATE_REPRESENTMENT                 = 'update_representment';
    const ACTION_MANUAL_HANDLE_ALERT                  = 'manual_handle_alert';
    const ACTION_MANUAL_HANDLE_REPRESENTMENT          = 'manual_handle_representment';
    const ACTION_FLAG_CHARGEBACK                      = 'flag_chargeback';
    const ACTION_STOP_SUBSCRIPTION                    = 'stop_subscription';
    const ACTION_REFUND                               = 'refund';
    const ACTION_REFUND_PARTIAL                       = 'refund_partial';
    const ACTION_VOID                                 = 'void';
    const ACTION_BLACKLIST                            = 'blacklist';
}