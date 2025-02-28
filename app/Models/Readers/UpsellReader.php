<?php

namespace App\Models\Readers;

use App\Models\Upsell;
use App\Traits\ModelImmutable;

/**
 * Class UpsellReader
 * Reader for the upsell_orders table, uses slave connection.
 * @package App\Models
 */
class UpsellReader extends Upsell
{
    use ModelImmutable;

    protected $connection = self::SLAVE_CONNECTION;

    /**
     * @var string
     */
    public $table = 'upsell_orders';
}
