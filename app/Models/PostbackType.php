<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class PostbackType
 * @package App\Models
 */
class PostbackType extends Model
{
    use Eloquence;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'vlkp_postback_types';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'token_type_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tokens()
    {
        return $this->hasMany(PostbackToken::class, 'type_id', 'token_type_id');
    }
}
