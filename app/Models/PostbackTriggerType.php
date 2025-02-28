<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class PostbackTriggerType
 * @package App\Models
 */
class PostbackTriggerType extends Model
{
    use Eloquence;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'vlkp_postback_trigger_types';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];
}
