<?php

namespace App\Models;

use App\Lib\Lime\LimeSoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ReturnReason extends Model
{
   use LimeSoftDeletes;

   public const REFUSED_RTS       = 1;
   public const UNDELIVERABLE     = 2;
   public const CUSTOMER_RETURNED = 3;
   public const OTHER             = 4;

   protected $primaryKey = 'id';
   protected $table      = 'tlkp_return_reason';
   protected $visible    = [
      'id',
      'name',
      'active',
      'deleted',
   ];
}
