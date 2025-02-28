<?php


namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Traits\ModelImmutable;

/**
 * Class TerminatingCycleType
 * Reader for the v_offer_terminating_cycle_types view, uses slave connection.
 * @package App\Models\Offer
 */
class TerminatingCycleType extends Model
{
    use Eloquence, ModelImmutable;

    const TYPE_HOLD = 1;
    const TYPE_SELF_RECUR = 2;
    const TYPE_RECUR_TO_PRODUCT_AND_HOLD = 3;
    const TYPE_RESTART = 4;
    const TYPE_COMPLETE = 5;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;

    /**
     * @var string
     */
    public $table = 'v_offer_terminating_cycle_types';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    public $visible = [
        'id',
        'name',
        'description',
    ];
}
