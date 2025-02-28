<?php
/**
 * Created by PhpStorm.
 * User: Trevor
 * Date: 4/2/2018
 * Time: 11:13 AM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Vertical
 *
 * @property string $name
 */
class Vertical extends Model
{
    // use Eloquence, Mappable;

    /**
     * @var string
     */
    protected $table  = 'vertical';

    /**
     * @var array
     */
    protected $hidden = [
        'active',
        'deleted',
        'immutable_flag',
        'date_in',
        'update_in',
        'created_id',
        'update_id',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'parent_id',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_active'    => 'active',
        'is_deleted'   => 'deleted',
        'is_immutable' => 'immutable_flag',
        'created_at'   => 'date_in',
        'updated_at'   => 'update_in',
        'created_by'   => 'created_id',
        'updated_by'   => 'update_id',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
