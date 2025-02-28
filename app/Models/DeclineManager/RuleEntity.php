<?php

namespace App\Models\DeclineManager;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class RuleEntity
 * @package App\Models
 */
class RuleEntity extends Model
{

    use Eloquence;

    /**
     * @var string
     */
    public $table = 'decline_salvage_rule_entity';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'entity_id',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'rule_id',
        'entity_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    protected function rule()
    {
        return $this->belongsTo(Rule::class, 'rule_id', 'id');
    }
}
