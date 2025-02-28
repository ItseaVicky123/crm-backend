<?php


namespace App\Models\Tax;

use App\Models\Campaign\Campaign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Lib\HasCreator;

/**
 * Class SalesTaxProfile
 * @package App\Models\Tax
 * @todo update sales tax module to use Lumen models https://sticky.atlassian.net/browse/DEV-1189
 */
class SalesTaxProfile extends Model
{
    use HasCreator;

    const CREATED_BY = 'created_id';
    const UPDATED_BY = 'updated_id';

    const UPDATED_AT = 'update_in';
    const CREATED_AT = 'date_in';

    /**
     * @var string
     */
    protected $table = 'tax_profile';

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'name',
        'description',
        'tax_region_id',
        'active',
        'tax_billing_address',
        'tax_after_shipping',
        'tax_level_id',
        'country_id',
        'state_id',
        'county_id',
        'city_id',
        'total_tax',
        'local_tax',
    ];

    /**
     * Campaigns associated with this sales tax profile.
     * @return BelongsToMany
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(
            Campaign::class,
            'campaign_tax_profile',
            'tax_profile_id',
            'campaign_id',
            'id',
            'c_id'
        );
    }
}