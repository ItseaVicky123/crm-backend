<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class OrderDocument
 * @package App\Models
 */
class OrderDocument extends Model
{
    use Eloquence, Mappable;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $guarded = [
        'id',
    ];

    /**
     * @var string[]
     */
    protected $visible = [
        'type',
        'document',
    ];

    /**
     * @var string[]
     */
    protected $maps = [
        'order_id' => 'orders_id',
        'type'     => 'doc_type',
        'hash'     => 'access_hash',
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'type',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orders_id', 'orders_id');
    }
}
