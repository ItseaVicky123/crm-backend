<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sofa\Eloquence\Eloquence;

/**
 * Class PostbackHeader
 * @package App\Models
 */
class PostbackHeader extends Model
{

    use SoftDeletes, Eloquence;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'postback_id',
        'key',
        'value',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'postback_id',
        'key',
        'value',
        'created_by',
        'updated_by',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function postback()
    {
        return $this->belongsTo(Postback::class);
    }
}
