<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class GenericFieldIndex
 * @package App\Models
 */
class GenericFieldIndex extends Model
{

    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';

    /**
     * @var string
     */
    protected $table = 'generic_fields_index';

    /**
     * @var string
     */
    protected $primaryKey = 'genericFieldsIndexId';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'         => 'genericFieldsIndexId',
        'created_at' => 'createdOn',
    ];
}
