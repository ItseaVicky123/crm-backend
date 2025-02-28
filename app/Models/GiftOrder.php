<?php


namespace App\Models;

use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;

/**
 * Class GiftOrder
 * @package App\Models
 */
class GiftOrder extends Model
{
    use HasCompositePrimaryKey;

    const UPDATED_AT = null;

    /**
     * @var string
     */
    public $table = 'order_gifts';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string[]
     */
    public $primaryKey = [
        'order_id',
        'type_id',
    ];

    /**
     * @var string[]
     */
    protected $visible = [
        'order_id',
        'email',
        'message',
    ];
}
