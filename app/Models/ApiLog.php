<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;


/**
 * Class ApiLog
 * @package App\Models
 */
class ApiLog extends Model
{
    use Eloquence;

    const CREATED_AT   = 'created_at';
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'api_logs';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'method_name',
        'route',
        'session_id',
        'attribute_name',
        'attribute_value',
        'created_at'
    ];

    protected $guarded = [
        'id',
        'created_at'
    ];
}
