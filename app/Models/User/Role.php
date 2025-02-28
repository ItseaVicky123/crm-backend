<?php

namespace App\Models\User;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Role
 * @package App\Models\User
 */
class Role extends Model
{
    use Eloquence, Mappable;

    /**
     * @var string
     */
    public $table = 'department_menu_role_user';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $maps = [
        'menu_id' => 'mid',
    ];

    /**
     * @var string[]
     */
    protected $guarded = [
        'id',
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'menu_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function menu_item()
    {
        return $this->hasOne(MenuItem::class, 'id', 'mid');
    }
}
