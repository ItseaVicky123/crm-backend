<?php


namespace App\Models;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RoleType
 * @package App\Models\BillingModel
 */
class RoleType extends Model
{
    use ModelImmutable;

    /**
     * @var string
     */
    public $table = 'menu_role_types';

    /**
     * @var bool
     */
    public $timestamps = false;
}
