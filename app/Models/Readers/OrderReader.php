<?php


namespace App\Models\Readers;

use App\Models\Order;
use App\Traits\ModelImmutable;

/**
 * Reader for the orders table, uses slave connection.
 * Class OrderReader
 * @package App\Models
 */
class OrderReader extends Order
{
    use ModelImmutable;

    protected $connection = self::SLAVE_CONNECTION;

    /**
     * @var string
     */
    public $table = 'orders';
}
