<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class UserDepartment
 * @package app\Models
 */
class UserDepartment extends Model
{

    use Eloquence, Mappable, LimeSoftDeletes;

    /**
     * @var string
     */
    protected $table = 'department';

    /**
     * @var array
     */
    protected $visible = [
        'name',
        'id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'department_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(UserDepartmentType::class, 'id', 'type_id');
    }
}
