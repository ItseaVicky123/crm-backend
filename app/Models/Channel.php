<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSequencer;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;

/**
 * Class Channel
 * @package App\Models
 *
 * @property string $name
 */
class Channel extends Model
{
    use Eloquence, Mappable, LimeSequencer, LimeSoftDeletes, HasCreator;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = 'update_in';

    /**
     * @var string
     */
    protected $table = 'channel';

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
    protected $maps = [
        'parent_name'  => 'parent.name',
        'created_at'   => self::CREATED_AT,
        'updated_at'   => self::UPDATED_AT,
        'is_active'    => 'active',
        'is_deleted'   => 'deleted',
        'is_immutable' => 'immutable_flag',
        'created_by'   => 'creator.name',
        'updated_by'   => 'updator.name',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }
}
