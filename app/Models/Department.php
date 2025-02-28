<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Builder;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;

class Department extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes;

    const TYPE_SYSTEM       = 1;
    const TYPE_API          = 2;
    const TYPE_CALL_CENTER  = 3;
    const TYPE_USER_DEFINED = 4;
    const CREATED_AT = 'date_in';
    const UPDATED_AT = 'update_in';

    /**
     * @var string
     */
    public $table = 'department';

    /**
     * @var int
     */
    public $perPage = 100;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'created_id',
        'type_id',
    ];

    /**
     * @var string[]
     */
    protected $maps = [
        'created_at' => self::CREATED_AT,
        'updated_at' => self::UPDATED_AT,
    ];

    /**
     * @var int[]
     */
    protected static $uiDepartmentsTypes = [
        self::TYPE_SYSTEM,
        self::TYPE_USER_DEFINED,
    ];

    protected static $apiDepartmentsTypes = [
        self::TYPE_SYSTEM,
        self::TYPE_CALL_CENTER,
        self::TYPE_USER_DEFINED,
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($deparment) {
            if (! $deparment->id) {
                $deparment->id = get_next_sequence('department');
            }
        });
    }

    /**
     * @return mixed
     */
    public function scopeForApi()
    {
        return $this->where('internal_flag', 0)
            ->whereIn('type_id', self::$apiDepartmentsTypes);
    }

    /**
     * @return int[]
     */
    public function getApiDepartmentTypes()
    {
        return self::$apiDepartmentsTypes;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return int[]
     */
    public function getUiDepartmentTypes()
    {
        return self::$uiDepartmentsTypes;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeForUI(Builder $query)
    {
        return $query->whereIn('type_id', self::$uiDepartmentsTypes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'department_menu_role_template', 'department_id');
    }
}
