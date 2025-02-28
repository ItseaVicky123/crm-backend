<?php

namespace App\Models\DeclineManager;

use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;
use App\Models\User;
use App\Traits\HasScheduleBits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;

/**
 * Class Profile
 * @package App\Models
 */
class Profile extends Model
{

    use Mappable, Eloquence, HasCreator, HasScheduleBits, LimeSoftDeletes;

    const CREATED_BY  = 'created_id';
    const UPDATED_BY  = 'updated_id';
    const UPDATED_AT  = 'update_in';
    const CREATED_AT  = 'date_in';
    const ACTIVE_FLAG = false;

    /**
     * @var string
     */
    public $table = 'decline_salvage_profile';
    protected $perPage = 250;


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
        'is_active'            => 'active',
        'attempt_count'        => 'attempt_cnt',
        'is_discount'          => 'discount_flag',
        'is_discount_shipping' => 'discount_shipping_flag',
        'is_default'           => 'default_flag',
        'process_percent'      => 'process_pct',
        'is_gateway_preserve'  => 'gateway_preserve_flag',
        'created_at'           => self::CREATED_AT,
        'updated_at'           => self::UPDATED_AT,
        'created_by'           => self::CREATED_BY,
        'updated_by'           => self::UPDATED_BY,
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'priority',
        'gateway_id',
        'schedule_type_id',
        'schedule_value',
        'schedule_frequencies',
        'schedule_days',
        'schedule_hour',
        'process_percent',
        'max_days',
        'discount_min',
        'is_gateway_preserve',
        'is_default',
        'attempt_count',
        'is_active',
        'is_discount',
        'is_discount_shipping',
        'creator',
        'updator',
        'created_at',
        'updated_at',
        'rules',
        'steps',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'is_gateway_preserve',
        'is_active',
        'is_default',
        'attempt_count',
        'is_discount',
        'is_discount_shipping',
        'process_percent',
        'creator',
        'updator',
        'created_at',
        'updated_at',
        'rules',
        'steps',
        'schedule_frequencies',
        'schedule_days',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'gateway_id',
        'is_gateway_preserve',
        'attempt_count',
        'is_discount',
        'is_discount_shipping',
        'discount_min',
        'schedule_type_id',
        'schedule_hour',
        'schedule_value',
        'process_percent',
        'max_days',
        'priority',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($profile) {
            $profile->created_by = \current_user(User::SYSTEM);

            if (! $profile->priority) {
                $profile->priority = DB::select(
                    DB::raw(
                        'SELECT MAX(priority) + 1 AS priority FROM decline_salvage_profile WHERE active = 1 AND deleted = 0'
                    ))[0]->priority;
            }
        });

        static::updating(function ($profile) {
            $profile->updated_by = \current_user(User::SYSTEM);
        });

        static::deleted(function ($profile) {
            $profile->update([
                'updated_by' => \current_user(User::SYSTEM),
            ]);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rules()
    {
        return $this->hasMany(Rule::class, 'profile_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRulesAttribute()
    {
        return $this->rules()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function steps()
    {
        return $this->hasMany(Step::class, 'profile_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getStepsAttribute()
    {
        return $this->steps()->get();
    }
}
