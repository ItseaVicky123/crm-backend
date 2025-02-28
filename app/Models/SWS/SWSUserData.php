<?php

namespace App\Models\SWS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class SWSUserData
 *
 * @property int $id
 * @property string $app_key
 * @property string $email
 * @property string $password
 * @property string $account_id
 * @property string $organization_id
 * @property string $name
 * @property string $lastname
 * @property string $organization_name
 * @property array $services
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static SWSUserData first()
 *
 */
class SWSUserData extends Model
{
    protected $table = 'all_clients_limelight.sws_user_data';

    protected $fillable = [
        'app_key',
        'email',
        'password',
        'account_id',
        'organization_id',
        'name',
        'lastname',
        'organization_name',
        'services',
    ];

    protected $casts = [
        'services' => 'array',
    ];

    protected static function booted()
    {
        // This table is on all_clients_limelight database, this scope is to ensure we get the record for the correct app key
        static::addGlobalScope(new AppKeyScope);
    }

    /**
     * @param array $attributes
     * @return SWSUserData
     */
    public static function firstOrCreate(array $attributes = []): SWSUserData
    {
        $attributes['app_key'] = CRM_APP_KEY;
        $instance              = self::query()->firstOrCreate($attributes);

        return $instance instanceof SWSUserData ? $instance : new SWSUserData();
    }

    public function getServicesAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }
}