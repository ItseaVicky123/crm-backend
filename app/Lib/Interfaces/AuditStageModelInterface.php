<?php

namespace App\Lib\Interfaces;
use Exception;

/**
 * Interface AuditStageModelInterface
 * @package App\Lib\Interfaces
 * Implemented on the module specific audit processes
 */
interface AuditStageModelInterface extends AuditStageInterface
{
   /**
    * Stage the changes to translate into audit record parameters
    * @return self
    */
   public function stageChanges(): AuditStageModelInterface;

   /**
    * Create the audit table record
    * @throws Exception
    * @return void
    */
   public function commitChanges(): void;
}
