<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class PostbackTrigger
 * @package App\Models
 */
class PostbackTriggerLookup extends Model
{

    use Eloquence;

    /**
     * @var bool
     */
    public $timestamps = false;

    public $perPage = 25;

    /**
     * @var string
     */
    protected $table = 'vlkp_postback_triggers';

    /**
     * @var array
     */
    protected $visible = [
        'trigger_id',
        'type_id',
        'url_type_id',
        'key',
        'name',
        'sort_order',
        'is_multi',
        'options',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'options',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
        return $this->hasMany(PostbackTriggerOption::class, 'trigger_id', 'trigger_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOptionsAttribute()
    {
        return $this->options()->get();
    }
}
