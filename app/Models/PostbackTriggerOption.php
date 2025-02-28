<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class PostbackTriggerOption
 * @package App\Models
 */
class PostbackTriggerOption extends Model
{

    use Eloquence;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'vlkp_postback_trigger_options';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'key',
        'trigger_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function trigger()
    {
        return $this->belongsTo(PostbackTrigger::class, 'trigger_id', 'trigger_id');
    }
}
