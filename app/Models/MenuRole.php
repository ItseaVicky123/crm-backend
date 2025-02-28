<?php

namespace App\Models;

use App\Lib\Lime\LimeSoftDeletes;
use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class MenuRole
 * @package App\Models
 */
class MenuRole extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes, ModelImmutable;

    const CREATED_AT   = 'date_in';
    const UPDATED_AT   = false;
    const ACTIVE_FLAG  = 'active';
    const DELETED_FLAG = 'deleted';

    const SHIPPING_ID             = 3;
    const ORDERS_ID               = 5;
    const PROSPECTS_ID            = 6;
    const CUSTOMERS_ID            = 7;
    const PLACE_ORDER_ID          = 10;
    const CAMPAIGNS_ID            = 13;
    const ACCOUNTS_PERMISSIONS_ID = 20;
    const EMAIL_TEMPLATE_ID       = 32;
    const SMTP_SERVERS_ID         = 33;
    const EMAIL_NOTIFICATIONS_ID  = 34;
    const CATEGORIES_ID           = 39;
    const PRODUCTS_ID             = 40;
    const SUBSCRIPTION_MANAGE_ID  = 48;
    const COUPONS_ID              = 54;
    const ANALYTICTS_REPORT_ID    = 74;
    const REPORTS_ID              = 82;
    const DECLINE_MANAGER_ID      = 86;
    const BILLING_MODELS_ID       = 95;
    const OFFERS_ID               = 97;
    const POST_BACKS_ID           = 109;
    const CUSTOM_FIELDS_ID        = 110;
    const TRIAL_WORKFLOW_ID       = 118;
    const AFFILIATES_ID           = 120;
    const VOLUME_DISCOUNT_ID      = 121;
    const BLACKLIST_V2_ID         = 122;
    const MY_PROVIDERS_ID         = 91;
    const INITIAL_DUNNING         = 129;
    const STICKY_CHECKOUT         = 130;

    /**
     * @var string
     */
    public $table = 'menu_roles';

    public $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $maps = [
        'menu_id'    => 'mid',
        'created_at' => self::CREATED_AT,
        'is_active'  => self::ACTIVE_FLAG,
        'is_deleted' => self::DELETED_FLAG,
        'role_id'    => 'id',
    ];

    /**
     * @var string[]
     */
    protected $visible = [
        'id',
        'name',
        'menu_item',
        'role_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function menu_item()
    {
        return $this->hasOne(MenuItem::class, 'id', 'mid');
    }
}
