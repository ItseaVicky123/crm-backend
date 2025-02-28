<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class ValueAddService extends Model
{
    use Mappable;
    use Eloquence;

    /**
     * @var string
     */
    protected $primaryKey = 'service_id';
    protected $table      = 'value_add_service';

    /**
     * @var int
     */

    protected $visible = [
       'id',
       'is_active',
       'is_managed'
    ];

    protected $maps = [
       'id'         => 'service_id',
       'is_active'  => 'active',
       'is_managed' => 'managed_flag',
    ];

    protected $appends = [
       'id',
       'is_active',
       'is_managed'
    ];

    protected $fillable = [
        'active'
    ];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function value_add_service_type()
    {
        return $this->hasOne(ValueAddServiceType::class, 'id', 'id');
    }


    /**
     * @param $query
     * @return mixed
     */
    public function get_value_add_service_names($query)
    {
        return DB::table('vlkp_value_add_service')
                   ->leftJoin('value_add_service', 'value_add_service.service_id', '=', 'vlkp_value_add_service.id')
                   ->whereColumn([
                       ['value_add_service.active',        '=', 1],
                       ['value_add_service.external_flag', '=', 0]
                   ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function configurations()
    {
        return $this->hasMany(ValueAddServiceConfiguration::class, 'service_id', 'service_id');
    }

    /**
     * @param Builder $query
     * @param int $serviceId
     * @return Builder
     */
    public function scopeForService(Builder $query, int $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * @param int $serviceId
     * @param bool $ignoreCache
     * @return bool
     */
    public function isEnabled(int $serviceId, bool $ignoreCache = false) : bool
    {
        if (! $ignoreCache && isset($_SESSION['_CACHE_']['VAS_ENABLED'][$serviceId])) {
            return $_SESSION['_CACHE_']['VAS_ENABLED'][$serviceId];
        }

        return $_SESSION['_CACHE_']['VAS_ENABLED'][$serviceId] = self::forService($serviceId)->where('active', 1)->exists();
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     * @throws \Exception
     */
    public function getConfiguration($key, $default = null)
    {
        try {
            return $this->configurations()
                ->where('key', $key)
                ->firstOrFail()
                ->value;
        } catch (\Exception $e) {
            if (!is_null($default)) {
                return $default;
            } else {
                throw $e;
            }
        }
    }
}
