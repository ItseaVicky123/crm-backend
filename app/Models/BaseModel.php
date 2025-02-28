<?php


namespace App\Models;

use App\Traits\MappableTrait;
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
    // use Eloquence, Mappable, ModelReader, ModelWriter, ModelCommenter;
    use ModelReader, ModelWriter, ModelCommenter, MappableTrait;

    protected $maps        = [];
    protected $appends     = [];
    protected $perPage     = 15;
    public $maxPerPage     = 15;
    const WEB_CONNECTION   = 'mysql';
    const SLAVE_CONNECTION = 'mysql_slave';
    const API_CONNECTION   = 'mysql_api';

    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        if (!isset($this->maps)) {
            $this->maps = [];
        }

        $this->appends = array_keys($this->maps);
    }
}

