<?php

namespace App\Models\BinManagement;

use App\Models\Campaign\Campaign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Lib\HasCreator;

/**
 * Class BinProfile
 * @package App\Models\BinManagement
 * @todo use Lumen models for BIN Management https://sticky.atlassian.net/browse/DEV-1188
 */
class BinProfile extends Model
{
    use HasCreator;

    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    const UPDATED_AT = 'update_in';
    const CREATED_AT = 'date_in';

    /**
     * @var string
     */
    protected $table = 'bin_profile';

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'name',
        'active',
        'is_prepaid',
        'active',
        'block_type',
    ];

    /**
     * Campaigns associated with this bin profile.
     * @return BelongsToMany
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(
            Campaign::class,
            'bin_campaign_jct',
            'bin_profile_id',
            'campaign_id',
            'id',
            'c_id'
        );
    }

    /**
     * User defined bins associated with this bin profile.
     * @return BelongsToMany
     */
    public function bins(): BelongsToMany
    {
        return $this->belongsToMany(
            UserDefinedBin::class,
            'bin_profile_jct',
            'profile_id',
            'bin_id',
            'id',
            'id'
        );
    }
}
