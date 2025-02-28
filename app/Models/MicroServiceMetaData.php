<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MicroServiceMetaData
 * @package App\Models
 */
class MicroServiceMetaData extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $table = 'vlkp_limelight_service';

    /**
     * @var array
     */
    protected $visible = [
        'name',
        'service_key',
        'uri_base',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope());
    }
}
