<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;

/**
 * Class OrderUtm
 * @package App\Models
 */
class OrderUtm extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'date_in';

    /**
     * @var string
     */
    public $table = 'order_utm';

    /**
     * @var string
     */
    protected $primaryKey = 'order_id';

    /**
     * @var array
     */
    protected $visible = [
        'order_id',
        'source',
        'medium',
        'campaign',
        'term',
        'content',
        'device_category',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at' => self::CREATED_AT,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orders_id', 'order_id');
    }
}
