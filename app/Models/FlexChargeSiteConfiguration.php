<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class FlexChargeSiteConfiguration
 * @package App\Models
 */
class FlexChargeSiteConfiguration extends Model
{
    use Eloquence, Mappable;

    public CONST CIT_CAMPAIGN_FIELD_TYPE = 'CIT';
    public CONST MIT_CAMPAIGN_FIELD_TYPE = 'MIT';

    protected $table = 'flexcharge_site_configurations'; // Adding this because we already use flexcharge and not flex_charge on the DB

    protected $fillable = [
        'gateway_profile_id', 'campaign_id', 'type', 'flexcharge_site_id'
    ];

    /**
     * @return BelongsTo
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo
     */
    public function gatewayProfile(): BelongsTo
    {
        return $this->belongsTo(GatewayProfile::class);
    }

    /**
     * Wrapper function for inserting a record, doing this because the previous approach was to add everything in one record
     * instead of one record per configuration, this function must be called for each setting that needs to be added.
     *
     * @param int $gatewayProfileId
     * @param int $campaignId
     * @param string $type
     * @param string $flexChargeSiteId
     * @return FlexChargeSiteConfiguration
     */
    private static function addConfiguration(int $gatewayProfileId, int $campaignId, string $type, string $flexChargeSiteId): FlexChargeSiteConfiguration {
        return self::create([
            'gateway_profile_id' => $gatewayProfileId,
            'campaign_id'        => $campaignId,
            'type'               => $type,
            'flexcharge_site_id' => $flexChargeSiteId
        ]);
    }

    /**
     * Wrapper function to add a CIT Campaign
     *
     * @param int $gatewayProfileId
     * @param int $campaignId
     * @param string $flexChargeSiteId
     * @return FlexChargeSiteConfiguration
     */
    public static function addCITCampaign(int $gatewayProfileId, int $campaignId, string $flexChargeSiteId): FlexChargeSiteConfiguration {
        return self::addConfiguration($gatewayProfileId, $campaignId, self::CIT_CAMPAIGN_FIELD_TYPE, $flexChargeSiteId);
    }

    /**
     * Wrapper function to add an MIT Campaign
     *
     * @param int $gatewayProfileId
     * @param int $campaignId
     * @param string $flexChargeSiteId
     * @return FlexChargeSiteConfiguration
     */
    public static function addMITCampaign(int $gatewayProfileId, int $campaignId, string $flexChargeSiteId): FlexChargeSiteConfiguration {
        return self::addConfiguration($gatewayProfileId, $campaignId, self::MIT_CAMPAIGN_FIELD_TYPE, $flexChargeSiteId);
    }

    /**
     * Scope query to only include configurations of a given profile id
     *
     * @param Builder $query
     * @param int $gatewayProfileId
     * @return void
     */
    public function scopeOfGatewayProfileId(Builder $query, int $gatewayProfileId): void {
        $query->where('gateway_profile_id', $gatewayProfileId);
    }

    /**
     * Scope query to only include CIT configurations
     *
     * @param Builder $query
     * @return void
     */
    public function scopeCIT(Builder $query): void {
        $query->where('type', self::CIT_CAMPAIGN_FIELD_TYPE);
    }

    /**
     * Scope query to only include CIT configurations
     *
     * @param Builder $query
     * @return void
     */
    public function scopeMIT(Builder $query): void {
        $query->where('type', self::MIT_CAMPAIGN_FIELD_TYPE);
    }
}