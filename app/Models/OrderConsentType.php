<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class OrderConsentType
 * Reader for the v_order_consent_types view, uses slave connection.
 * @package App\Models
 */
class OrderConsentType extends Model
{
    use ModelImmutable;

    const TYPE_ID_EMAIL = 1;
    const TYPE_ID_CALL = 2;
    const TYPE_ID_API = 3;
    const TYPE_ID_AUTO = 4;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_order_consent_types';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];
}
