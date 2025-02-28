<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use  App\Traits\HasCompositePrimaryKey;

/**
 * Class UserOptOut
 * @package App\Models
 */
class UserOptOut extends Model
{
    use Eloquence, HasCompositePrimaryKey;

    const FEATURE_ID = 1;

    /**
     * @var string
     */
    protected $table = 'user_opt_out';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $primaryKey = [
        'user_id',
        'feature_id',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'feature_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'admin_id', 'user_id');
    }
}
