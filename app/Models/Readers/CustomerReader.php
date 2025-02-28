<?php


namespace App\Models\Readers;

use App\Models\Customer;
use App\Traits\ModelImmutable;

/**
 * Reader for the customers table, uses slave connection.
 * Class CustomerReader
 * @package App\Models
 */
class CustomerReader extends Customer
{
    use ModelImmutable;

    protected $connection = self::SLAVE_CONNECTION;

    /**
     * @var string
     */
    public $table = 'customers';
}
