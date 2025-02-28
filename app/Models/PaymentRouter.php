<?php

namespace App\Models;

use App\Models\Campaign\Campaign;

/**
 * Class PaymentRouter
 *
 * @property int $id
 */
class PaymentRouter extends BaseModel
{
    protected $table = 'load_balance_configuration_profile';

    public function campaigns()
    {
        return $this->belongsTo(Campaign::class, 'lbc_id');
    }
}
