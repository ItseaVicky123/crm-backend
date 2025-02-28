<?php


namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Traits\ModelImmutable;

/**
 * Class CycleType
 * Reader for the v_offer_cycle_types view, uses slave connection.
 * @package App\Models\Offer
 */
class CycleType extends Model
{
    use Eloquence, ModelImmutable;

    const TYPE_SELF   = 1;
    const TYPE_CUSTOM = 2;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_offer_cycle_types';

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
    ];
}
