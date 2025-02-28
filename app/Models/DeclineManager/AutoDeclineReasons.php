<?php

namespace App\Models\DeclineManager;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class AutoDeclineReasons
 * @package App\Models\DeclineManager
 */
class AutoDeclineReasons extends Model
{
    use Eloquence, Mappable;

    /**
     * @var string
     */
    protected $table = 'vlkp_auto_decline_reason';

    /**
     * @var string[] $fillable
     */
    protected $fillable = [
        'id',
        'reason',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'reason',
    ];
}