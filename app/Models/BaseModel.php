<?php


namespace App\Models;

use App\Traits\ModelCommenter;
use App\Traits\ModelReader;
use App\Traits\ModelWriter;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseModel
 * @package App\Models
 */
class BaseModel extends Model
{
    use Eloquence, Mappable, ModelReader, ModelWriter, ModelCommenter;

    const WEB_CONNECTION   = 'mysql';
    const SLAVE_CONNECTION = 'mysql_slave';
    const API_CONNECTION   = 'mysql_api';

    public $maxPerPage     = 15;

    /**
     * An array of attribute mappings
     *
     * @var array
     */
    protected $maps       = [];
    protected $perPage    = 15;
}
