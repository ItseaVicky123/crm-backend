<?php

namespace App\Lib\Interfaces;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface AuditStageInterface
 * @package App\Lib\Interfaces
 * Standard interface for audit table creation, implemented by base classes
 */
interface AuditStageInterface
{
   /**
    * Set the model instance to instance property
    * @param Model $instance
    * @return void
    */
   public function setInstance(Model &$instance);

   /**
    * Set record updates array
    * @param array $updates
    * @return void
    */
   public function setUpdates(array &$updates);
}