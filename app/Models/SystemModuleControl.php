<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemModuleControl extends Model
{

    public const DISABLE_WEBFORM_CAMPAIGN_OPTION     = 33;
    public const BILLING_MODELS_NEXT_GEN             = 35;
    public const BILLING_MODELS_MANAGE_OFFERS        = 38;
    public const PRODUCT_BUNDLES                     = 41;
    public const OFFER_PREPAID                       = 43;
    public const USE_DECLINE_MANAGER_EDIT_PAGE       = 74;
    public const BUNDLE_UPSELLS                      = 72;
    public const GROUP_UPSELLS_BY_LATEST_REBILL_DATE = 129;

    public const DNVB_MODE_IDS = [
        self::DISABLE_WEBFORM_CAMPAIGN_OPTION,
        self::BILLING_MODELS_NEXT_GEN,
        self::BILLING_MODELS_MANAGE_OFFERS,
        self::PRODUCT_BUNDLES,
        self::OFFER_PREPAID,
    ];

    protected $table = 'system_module_control';

    protected $fillable = [
        'active'
    ];

    const UPDATED_AT = 'update_in';
}
