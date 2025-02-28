<?php

namespace App\Models\DeclineManager;

use App\Models\EntityType;
use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class RuleType
 * @package App\Models
 */
class RuleType extends Model
{

    use Eloquence;

    /**
     * @var string
     */
    public $table = 'vlkp_decline_salvage_rule';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    public static function boot()
    {
        static::addGlobalScope(new ActiveScope());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    protected function rule()
    {
        return $this->belongsTo(Rule::class, 'type_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    protected function entity_type()
    {
        return $this->hasOne(EntityType::class, 'id', 'entity_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getEntityTypeAttribute()
    {
        return $this->entity_type()->first();
    }
}
