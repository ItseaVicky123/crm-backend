<?php

namespace App\Models\DeclineManager;

use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;

/**
 * Class Rule
 * @package App\Models
 */
class Rule extends Model
{

    use LimeSoftDeletes, Mappable, Eloquence, HasCreator;

    const CREATED_BY = 'created_id';
    const UPDATED_BY = 'updated_id';
    const UPDATED_AT = 'update_in';
    const CREATED_AT = 'date_in';

    /**
     * @var string
     */
    public $table = 'decline_salvage_rule';

    /**
     * @var array
     */
    protected $dates = [
        'date_in',
        'update_in',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_active'  => 'active',
        'created_at' => self::CREATED_AT,
        'updated_at' => self::UPDATED_AT,
        'created_by' => self::CREATED_BY,
        'updated_by' => self::UPDATED_BY,
    ];

    /**
     * @var array
     */
    protected $appends = [
        'is_active',
        'creator',
        'updator',
        'created_at',
        'updated_at',
        'type',
        'entities',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'type_id',
        'creator',
        'updator',
        'created_at',
        'updated_at',
        'type',
        'entities',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'profile_id',
        'type_id',
        'value',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($rule) {
            $rule->created_by = \current_user(User::SYSTEM);
        });

        static::updating(function ($rule) {
            $rule->updated_by = \current_user(User::SYSTEM);
        });

        static::deleting(function ($rule) {
            $rule->entities()->delete();
        });

        static::deleted(function ($rule) {
            $rule->update([
                'updated_by' => \current_user(User::SYSTEM),
            ]);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    protected function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    protected function type()
    {
        return $this->hasOne(RuleType::class, 'id', 'type_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    protected function getTypeAttribute()
    {
        return $this->type()->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function entities()
    {
        return $this->hasMany(RuleEntity::class, 'rule_id', 'id');
    }

    /**
     * @return array|\Illuminate\Support\Collection
     */
    public function getEntitiesAttribute()
    {
        if ($entities = $this->entities()->get()) {
            return $entities->pluck('entity_id');
        }

        return [];
    }
}
