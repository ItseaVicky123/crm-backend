<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class PostbackTrigger
 * @package App\Models
 */
class PostbackTrigger extends Model
{
    use Eloquence;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'trigger_id',
        'trigger_option_id',
        'type_id'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'postback_id',
        'trigger_id',
        'trigger_option_id',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'type_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lookup()
    {
        return $this->hasOne(PostbackTriggerLookup::class, 'trigger_id', 'trigger_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function option()
    {
        return $this->hasOne(PostbackTriggerOption::class, 'id', 'trigger_option_id');
    }

    /**
     * @return mixed
     */
    public function getTypeIdAttribute()
    {
        return $this->lookup()->first()->type_id;
    }
}
