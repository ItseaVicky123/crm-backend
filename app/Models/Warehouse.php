<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Campaign\Campaign;
use App\Models\AllProvider;

/**
 * Class Warehouse
 * @package App\Models
 * @property int    $id primary key
 * @property string $name
 * @property string $description
 * @property string $address_1
 * @property string $address_2
 * @property string $city
 * @property string $state
 * @property string $zip
 * @property int    $country_id
 * @property bool   $active
 *
 */
class Warehouse extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'description',
        'address_1',
        'address_2',
        'city',
        'state',
        'zip',
        'country_id',
        'country',
        'importProvider',
        'exportProvider',
        'fulfillment_id',
        'ftp_profile_id',
        'active',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'address_1',
        'address_2',
        'city',
        'state',
        'zip',
        'country_id',
        'fulfillment_id',
        'ftp_profile_id',
        'active'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'countries_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function importProvider()
    {
        return $this->belongsTo(AllProvider::class, 'fulfillment_id', 'profile_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function exportProvider()
    {
        return $this->belongsTo(AllProvider::class, 'ftp_profile_id', 'profile_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($warehouse) {
            $warehouse->created_by = $warehouse->created_by ?? get_current_user_id();
        });

        self::updating(function ($warehouse) {
            $warehouse->updated_by = $warehouse->updated_by ?? get_current_user_id();
        });
    }

}
