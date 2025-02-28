<?php


namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Traits\ModelImmutable;

/**
 * Class PrepaidDiscountType
 * Reader for the v_prepaid_discount_types view, uses slave connection.
 * @package App\Models\Offer
 */
class PrepaidDiscountType extends Model
{
    use Eloquence, ModelImmutable;

    const TYPE_PERCENT = 1;
    const TYPE_AMOUNT = 2;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;

    /**
     * @var string
     */
    public $table = 'v_prepaid_discount_types';

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
