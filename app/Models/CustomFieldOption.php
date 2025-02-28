<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Sofa\Eloquence\Eloquence;

/**
 * Class CustomFieldOption
 * @package App\Models
 */
class CustomFieldOption extends Model
{
    use SoftDeletes, Eloquence;

    const MAX_OPTIONS = 250;

    /**
     * @var array
     */
    protected $dates = ['deleted_at', 'created_at', 'updated_at'];

    /**
     * @var array
     */
    protected $fillable = [
        'custom_field_id',
        'value',
        'created_by',
        'updated_by',
        'deleted_at',
    ];

    protected $visible = [
        'id',
        'value',
    ];

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $custom_field_id;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var int
     */
    protected $created_by;

    /**
     * @var int
     */
    protected $updated_by;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function custom_field()
    {
        return $this->belongsTo(CustomField::class);
    }
}
