<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class MenuItem
 * Reader for the v_next_recurring_gateway view, uses slave connection.
 * @package App\Models
 */
class NextRecurringGateway extends Model
{
   use ModelImmutable;

   protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


   protected $table = 'v_next_recurring_gateway';

   public function order()
   {
      return $this->belongsTo(Order::class);
   }
}
