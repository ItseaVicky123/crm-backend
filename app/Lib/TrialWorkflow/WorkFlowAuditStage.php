<?php

namespace App\Lib\TrialWorkflow;
use App\Exceptions\TrialWorkflow\WorkflowAuditCreateException;
use App\Lib\AuditStage;
use App\Lib\Interfaces\AuditStageModelInterface;
use App\Models\TrialWorkflow\TrialWorkflowAudit;

/**
 * Class WorkFlowAuditStage
 * @package App\Lib\TrialWorkflow
 */
class WorkFlowAuditStage extends AuditStage implements AuditStageModelInterface
{
   /**
    * @var string[] $flatFieldNames
    */
   protected array $flatFieldNames = [
      'name',
      'is_active',
   ];

   /**
    * @var string[] $formatYesNo
    */
   protected array $formatYesNo = [
      'is_active',
   ];

   /**
    * Stage the audit record data by capturing changes to the existing workflow
    */
   public function stageChanges(): WorkFlowAuditStage
   {
      $this->auditRecordData = [];

      foreach ($this->flatFieldNames as $fieldName) {
         if ($this->updateRequestedFor($fieldName)) {
            $this->appendAuditRecordChanges([
               'trial_workflow_id'    => $this->instance->id,
               'field_name'           => $fieldName,
               'field_name_formatted' => $this->formatFieldName($fieldName),
               'previous_value'       => $this->formatFieldValue($fieldName, $this->instance->{$fieldName}),
               'new_value'            => $this->formatFieldValue($fieldName, $this->updates[$fieldName]),
            ]);
         }
      }

      return $this;
   }

   /**
    * Create the audit record
    * @throws WorkflowAuditCreateException
    * @return void
    */
   public function commitChanges(): void
   {
      foreach ($this->auditRecordData as $recordData) {
         if (! TrialWorkflowAudit::create($recordData)) {
            throw new WorkflowAuditCreateException;
         }
      }
   }
}
