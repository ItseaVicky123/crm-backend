<?php

namespace App\Models;

use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class TemplateAttribute
 * Reader for the v_template_attribute view, uses slave connection.
 * @package App\Models
 */
class TemplateAttribute extends Model
{
    use HasCompositePrimaryKey, ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_template_attribute';

    /**
     * @var array
     */
    protected $primaryKey = [
        'type_id',
        'name',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'name',
        'pointer_value',
        'type_id',
        'description',
        'type_name',
    ];
}
