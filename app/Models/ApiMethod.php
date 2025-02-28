<?php

namespace App\Models;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class ApiMethod
 *
 * Reader for the v_api_methods view, uses slave connection.
 *
 * @package App\Models
 */
class ApiMethod extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes, ModelImmutable;

    const UPDATED_AT = 'update_in';
    const CREATED_AT = 'date_in';
    const MEMBER_DELETE_ID = 102;
    const GET_TOKEN_ID = 227;
    const GET_CUSTOM_FIELDS_ID = 228;
    const POST_CUSTOM_FIELDS_ID = 229;
    const PUT_CUSTOM_FIELDS_ID = 230;
    const DELETE_CUSTOM_FIELDS_ID = 231;
    const GET_PROSPECTS_ID = 232;
    const POST_PROSPECTS_ID = 233;
    const PUT_PROSPECTS_ID = 234;
    const DELETEPROSPECTS_ID = 235;
    const GET_CUSTOMERS_ID = 236;
    const POST_CUSTOMERS_ID = 237;
    const PUT_CUSTOMERS_ID =238;
    const DELETE_CUSTOMERS_ID = 239;
    const GET_ORDERS_ID = 240;
    const POST_ORDERS_ID = 241;
    const PUT_ORDERS_ID = 242;
    const DELETE_ORDERS = 243;
    const GET_PRODUCTS_ID = 244;
    const POST_PRODUCTS_ID = 245;
    const PUT_PRODUCTS = 246;
    const DELETE_PRODUCTS_ID = 247;
    const GET_USERS_ID = 248;
    const POST_TOKENIZE_PAYMENT_ID = 249;
    const GET_COUPONS_ID = 250;
    const POST_OFFERS_ID = 251;
    const PUT_OFFERS_ID = 252;
    const DELETE_OFFERS = 253;
    const GET_OFFERS_ID = 254;
    const POST_CAMPAIGNS_ID = 255;
    const PUT_CAMPAIGNS_ID = 256;
    const DELETE_CAMPAIGNS_ID = 257;
    const GET_CAMPAIGNS_ID = 258;
    const GET_PROVIDERS_ID = 259;
    const POST_PROVIDERS_ID = 260;
    const PUT_PROVIDERS_ID = 261;
    const DELETE_PROVIDERS_ID = 262;
    const POST_BILLING_MODELS_ID = 263;
    const PUT_BILLING_MODELS =  264;
    const DELETE_BILLING_MODELS = 265;
    const GET_BILLING_MODELS = 266;
    const GET_CONTACTS_ID = 267;
    const POST_CONTACTS_ID = 268;
    const DELETE_CONTACTS_ID = 269;
    const PUT_CONTACTS_ID = 270;
    const GET_SHIPPING_ID = 271;
    const POST_SHIPPING_ID = 272;
    const DELETE_SHIPPING_ID = 273;
    const PUT_SHIPPING_ID = 274;
    const GET_IMAGES_ID = 275;
    const POST_IMAGES_ID = 276;
    const DELETE_IMAGES_ID = 277;
    const PUT_IMAGES_ID = 278;
    const POST_VALIDATE_ADDRESS_ID = 279;
    const POST_COUPONS_ID = 280;
    const PUT_COUPONS_ID = 281;
    const DELETE_COUPONS_ID = 282;
    const POST_PROVIDERS_ACTIONS_ID = 283;
    const POST_POSTBACKS = 288;

    // Trial workflow v2 routes
    //
   const TRIAL_WORKFLOW_GET_ID    = 284;
   const TRIAL_WORKFLOW_POST_ID   = 285;
   const TRIAL_WORKFLOW_PUT_ID    = 286;
   const TRIAL_WORKFLOW_DELETE_ID = 287;

    // System metadata routes
    //
    const GET_SHIPPING_PRICE_TYPE_ID = 289;

    // Affiliate module permissions
    //
    const GET_AFFILIATES_ID = 290;
    const POST_AFFILIATES_ID = 291;
    const PUT_AFFILIATES_ID = 292;
    const DELETE_AFFILIATES_ID = 293;
    const GET_AFFILIATE_PERMISSIONS_ID = 294;
    const POST_AFFILIATE_PERMISSIONS_ID = 295;
    const DELETE_AFFILIATE_PERMISSIONS_ID = 296;
    const GET_AFFILIATE_TYPES_ID = 297;

    // Volume discount permissions
    //
    const GET_VOLUME_DISCOUNT_ID    = 297;
    const POST_VOLUME_DISCOUNT_ID   = 298;
    const PUT_VOLUME_DISCOUNT_ID    = 299;
    const DELETE_VOLUME_DISCOUNT_ID = 300;

    // Subscription Order Routes
    //
    const PUT_SUBSCRIPTION_ORDER_ID = 302;

    // Blacklist permissions
    //
    const GET_BLACKLIST_V2_ID    = 303;

    const POST_BLACKLIST_V2_ID   = 304;

    const PUT_BLACKLIST_V2_ID    = 305;

    const DELETE_BLACKLIST_V2_ID = 306;

    const PAYMENT_ROUTER_VIEW    = 116;

    protected $connection = BaseModel::SLAVE_CONNECTION;

    /**
     * @var string
     */
    protected $table      = 'v_api_methods';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'type_id',
        'limit',
    ];

    /**
     * @var array
     */
    protected $maps    = [
        'is_active'  => 'active',
        'is_deleted' => 'deleted',
        'limit'      => 'method_limit',
        'name'       => 'method',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function api_user_permission()
    {
        return $this->belongsTo(ApiUserPermission::class, 'api_method_id', 'id');
    }
}
