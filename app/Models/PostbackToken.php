<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class PostbackToken
 * @package App\Models
 */
class PostbackToken extends Model
{

    use Eloquence;

    /**
     * @var bool
     */
    public $timestamps = false;

    protected $perPage = 250;

    /**
     * @var string
     */
    protected $table = 'vlkp_postback_tokens';

    /**
     * @var array
     */
    protected $visible = [
        'postback_type_id',
        'name',
        'pointer_value',
        'description',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'postback_type_id',
    ];

    /**
     * @return mixed
     */
    public function getPostbackTypeIdAttribute()
    {
        return $this->getAttribute('type_id');
    }
}
