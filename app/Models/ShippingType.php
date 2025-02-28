<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class ShippingType
 * @package App\Models
 */
class ShippingType extends Model
{
    use LimeSoftDeletes, Eloquence, Mappable;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = 'update_in';

    protected $table = 'shipping_type';
    protected $primaryKey = 's_type_id';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'code',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'name',
        'code',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'         => 's_type_id',
        'name'       => 's_type_name',
        'code'       => 's_type_code',
        'created_at' => 'date_in',
        'updated_at' => 'update_in',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shipping()
    {
        return $this->belongsTo(Shipping::class, 's_type_id', 's_type_id');
    }
}
