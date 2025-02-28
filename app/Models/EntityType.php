<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Entity
 * @package App\Models
 */
class EntityType extends Model
{
    use Eloquence, Mappable;

    /**
     * @var string
     */
    protected $table = 'vlkp_entity';

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
    protected $allowed_types = [
        2,
        3,
        13,
    ];

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $table_name;

    /**
     * @var string
     */
    protected $view_name;

    /**
     * @var string
     */
    protected $pk_col;

    /**
     * @var string
     */
    protected $name_col;

    /**
     * @var string
     */
    protected $record_class;

    /**
     * @var string
     */
    protected $collection_class;

    /**
     * @var int bool
     */
    protected $pk_visible_flag;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function custom_field()
    {
        return $this->belongsTo(CustomField::class);
    }

    /**
     * @return mixed
     */
    public function scopeForApi()
    {
        return $this->whereIn('id', $this->allowed_types);
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public function customFieldTypeAllowed($type = 0)
    {
        return in_array($type, $this->allowed_types);
    }
}
