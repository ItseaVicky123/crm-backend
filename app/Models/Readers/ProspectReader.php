<?php


namespace App\Models\Readers;

use App\Models\Prospect;
use App\Traits\ModelImmutable;

/**
 * Reader for the prospects table, uses slave connection.
 * Class ProspectReader
 * @package App\Models
 */
class ProspectReader extends Prospect
{
    use ModelImmutable;

    protected $connection = self::SLAVE_CONNECTION;

    /**
     * @var string
     */
    public $table = 'prospects';
}
