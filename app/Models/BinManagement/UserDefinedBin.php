<?php

namespace App\Models\BinManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Lib\HasCreator;

/**
 * Class UserDefinedBin
 * @package App\Models\BinManagement
 * @todo use Lumen models for BIN Management https://sticky.atlassian.net/browse/DEV-1188
 */
class UserDefinedBin extends Model
{
    use HasCreator;

    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    const UPDATED_AT = 'update_in';
    const CREATED_AT = 'date_in';

    /**
     * @var string
     */
    protected $table = 'bin_user_defined';

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'name',
        'bin_range_start',
        'bin_range_end',
        'active',
    ];

    /**
     * @return BelongsToMany
     */
    public function binProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            BinProfile::class,
            'bin_profile_jct',
            'bin_id',
            'profile_id',
            'id',
            'id'
        );
    }
}
