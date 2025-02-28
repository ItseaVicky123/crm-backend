<?php

namespace App\Models\TrialWorkflow;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PairedProductDelay
 * @package App\Models\TrialWorkflow
 * Mechanism for delaying paired product until the next opportunity
 */
class PairedProductDelay extends Model
{
   /**
    * @var string[] $fillable
    */
   protected $fillable = [
      'order_id',
      'order_type_id',
      'product_id',
      'variant_id',
   ];
}