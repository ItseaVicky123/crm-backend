<?php

namespace App\Models\BillingModel;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Traits\ModelImmutable;

/**
 * Class RelativeDateType
 * Reader for the v_billing_model_relative_date_types view, uses slave connection.
 * @package App\Models\BillingModel
 */
class RelativeDateType extends Model
{
    use Eloquence, ModelImmutable;

    const MONTHLY = 1;
    const YEARLY = 2;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_billing_model_relative_date_types';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];
}
