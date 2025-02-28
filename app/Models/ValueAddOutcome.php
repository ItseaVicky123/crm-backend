<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class ValueAddOutcome
 * Reader for the v_gateway_supported_features view, uses slave connection.
 * @package App\Models
 */
class ValueAddOutcome extends Model
{
    use ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_value_add_outcome';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'label',
        'type_id',
        'account_id',
        'created_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function value_add_transaction()
    {
        return $this->belongsTo(ValueAddTransaction::class, 'outcome', 'id');
    }
}
