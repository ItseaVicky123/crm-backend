<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;
use App\Lib\HasCreator;

/**
 * Class ReturnProfile
 * @package App\Models
 */
class ReturnProfile extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes, HasCreator;

    /**
     * @var string
     */
    public $table = 'returns_profile';

    public $maps = [
        'is_rma_required' => 'rma_required_flag',
        'is_chargeback'   => 'chargeback_flag',
        'is_trial'        => 'trial_flag',
    ];
}
