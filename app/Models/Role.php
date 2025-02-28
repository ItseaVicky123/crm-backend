<?php

namespace App\Models;

use App\Lib\Lime\LimeSoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Role
 * @package App\Models
 */
class Role extends Model
{
    const CREATED_AT = 'date_in';
    const START_RECURRING_ID = 5;
    const STOP_RECURRING_ID = 6;
    const RESET_RECURRING = 7;
    const VOID_ORDER = 11;
    const SAVE_ORDER_ID = 17;
    const VIEW_CUSTOMER_HISTORY_ID = 18;
    const DELETE_CUSTOMER_ID = 19;
    const VIEW_ORDERS_ID = 20;
    const ADD_CATEGORY_ID = 22;
    const EDIT_CATEGORY_ID = 24;
    const DELETE_CATEGORY_ID = 25;
    const ADD_PRODUCT_ID = 26;
    const EDIT_METHOD_ID = 28;
    const DELETE_METHOD_ID = 29;
    const ADD_METHOD_ID = 30;
    const EDIT_GROUP_ID = 31;
    const EDIT_CUSTOMER_ID = 34;
    const DISABLE_CAMPAIGN_ID = 43;
    const EDIT_CAMPAIGN_ID = 44;
    const ADD_EMPLOYEE_ID = 57;
    const EMPLOYEE_PERMISSIONS_ID = 58;
    const EDIT_EMPLOYEE_ID = 59;
    const DELETE_EMPLOYEE_ID = 60;
    const ORDERS_ID = 68;
    const CUSTOMERS_ID = 70;
    const EDIT_PRODUCT = 93;
    const DELETE_PRODUCT_ID = 94;
    const DELETE_PROSPECT_ID = 95;
    const EDIT_PROSPECTS_ID = 97;
    const EDIT_DEPARTMENT_ID = 103;
    const ADD_EMAIL_EVENT_ID = 109;
    const EDIT_EMAIL_EVENT = 111;
    const DELETE_EMAIL_EVENT_ID = 112;
    const ADD_CAMPAIGN_ID = 145;
    const DELETE_CAMPAIGN_ID = 146;
    const COPY_CAMPAIGN_ID = 147;
    const FORECAST_MANAGMENT_ID = 163;
    const ADD_PROSPECT_ID = 167;
    const ADD_COUPON_PROFILE_ID = 179;
    const EDIT_COUPON_PROFILE_ID = 180;
    const DELETE_COUPON_PROFILE_ID = 181;
    const ADD_SALVAGE_PROFILE_ID = 306;
    const EDIT_SALVAGE_PROFILE_ID = 307;
    const DELETE_SALVAGE_PROFILE_ID = 308;
    const ADD_BILLING_MODEL_ID = 332;
    const  EDIT_BILLING_MODEL_ID = 333;
    const DELETE_BILLING_MODEL_ID = 335;
    const ADD_OFFERS_ID = 337;
    const EDIT_OFFERS_ID = 338;
    const DELETE_OFFERS_ID = 340;
    const SETUP_GUIDE_ID = 365;
    const ADD_POST_BACKS_ID = 370;
    const EDIT_POST_BACKS_ID = 371;
    const DELETE_POST_BACKS = 372;
    const ADD_CUSTOM_FIELDS_ID = 377;
    const EDIT_CUSTOM_FIELDS_ID = 378;
    const DELETE_CUSTOM_FIELDS_ID = 379;
    const SUBSCRIPTION_CREDITS_ID = 383;
    const APPLY_CONSENT_ID = 9538;

    // Trial workflow legacy routes
    //
    const TRIAL_WORKFLOW_GET_ID    = 9546;
    const TRIAL_WORKFLOW_POST_ID   = 9547;
    const TRIAL_WORKFLOW_PUT_ID    = 9548;
    const TRIAL_WORKFLOW_DELETE_ID = 9549;
    const TRIAL_WORKFLOW_COPY_ID   = 9550;

    // Affiliate module permissions
    //
    const GET_LIST_AFFILIATES_ID      = 9552;
    const GET_ONE_AFFILIATES_ID       = 9553;
    const CREATE_AFFILIATES_ID        = 9554;
    const UPDATE_AFFILIATES_ID        = 9555;
    const DELETE_AFFILIATES_ID        = 9556;
    const GRANT_ACCESS_AFFILIATES_ID  = 9557;
    const REVOKE_ACCESS_AFFILIATES_ID = 9558;
    const CLEAR_ACCESS_AFFILIATES_ID  = 9559;
    const GET_AFFILIATE_TYPES_ID      = 9561;

    // Volume discounts
    //
    const GET_LIST_VOLUME_DISCOUNTS = 9564;
    const GET_ONE_VOLUME_DISCOUNTS  = 9565;
    const CREATE_VOLUME_DISCOUNTS   = 9566;
    const UPDATE_VOLUME_DISCOUNTS   = 9567;
    const DELETE_VOLUME_DISCOUNTS   = 9568;
    const ATTACH_VOLUME_DISCOUNTS   = 9569;

    // Blacklist
    //
    const GET_BLACKLIST_V2    = 9576;

    const POST_BLACKLIST_V2   = 9573;

    const PUT_BLACKLIST_V2    = 9574;

    const DELETE_BLACKLIST_V2 = 9575;

    // Initial Dunning
    const INITIAL_DUNNING = 9585;

    // Sticky Checkout
    const STICKY_CHECKOUT = 9586;

    use LimeSoftDeletes;

    /**
     * @var string
     */
    protected $table = 'menu_roles';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role_type()
    {
        return $this->belongsTo(RoleType::class, 'type', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function role_users()
    {
        return $this->hasMany(\App\Models\User\Role::class, 'role_id', 'id');
    }
}
