<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomFieldType
 * @package App\Models
 */
class CustomFieldType extends Model
{
    use Eloquence, SoftDeletes;

    CONST TYPE_TEXT        = 1;
    CONST TYPE_NUMERIC     = 2;
    CONST TYPE_DATE        = 3;
    CONST TYPE_BOOLEAN     = 4;
    CONST TYPE_ENUMERATION = 5;

    /**
     * @var string
     */
    protected $table = 'all_clients_limelight.tlkp_custom_field_types';

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
    protected $dates = [
        'created_at',
        'deleted_at',
    ];

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    // Dates
    //
    protected $created_at;
    protected $deleted_at;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function custom_field()
    {
        return $this->belongsTo(CustomField::class, 'field_type_id', 'id');
    }
}
