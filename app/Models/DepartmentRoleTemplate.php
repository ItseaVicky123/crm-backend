<?php

namespace App\Models;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DepartmentRoleTemplate
 * @package App\Models
 */
class DepartmentRoleTemplate extends Model
{
    use ModelImmutable;

    /**
     * @var string
     */
    public $table = 'department_menu_role_template';

    /**
     * @var bool
     */
    public $timestamps = false;

    protected $visible = [
        'mid',
    ];
}
