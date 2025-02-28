<?php

namespace App\Lib\TrialWorkflow;
use App\Exceptions\TrialWorkflow\WorkflowUnitAuditCreateException;
use App\Lib\AuditStage;
use App\Lib\Interfaces\AuditStageModelInterface;
use App\Models\TrialWorkflow\TrialWorkflowAudit;
use App\Models\TrialWorkflow\TrialWorkflowUnit;
use App\Models\TrialWorkflow\TrialWorkflowUnitAudit;

/**
 * Class WorkflowUnitAuditStage
 * @package App\Lib\TrialWorkflow
 */
class WorkflowUnitAuditStage extends AuditStage implements AuditStageModelInterface
{
   /**
    * @var string[] $flatFieldNames
    */
   protected array $flatFieldNames = [
      'step_number',
      'name',
      'duration',
      'price',
      'product_id',
      'is_shippable',
      'is_notifiable',
      'is_one_time_pairable',
   ];

   /**
    * @var string[] $formatYesNo
    */
   protected array $formatYesNo = [
      'is_parent_cancellable',
      'is_shippable',
      'is_notifiable',
      'is_one_time_pairable',
   ];

   /**
    * @var string[][] $formatCustomMap
    */
   protected array $formatCustomMap = [];

   /**
    * Stage the audit record data by capturing changes to the existing workflow unit
    */
   public function stageChanges(): WorkflowUnitAuditStage
   {
      $this->auditRecordData = [];

      foreach ($this->flatFieldNames as $fieldName) {
         if ($this->updateRequestedFor($fieldName)) {
            $this->appendAuditRecordChanges([
               'trial_workflow_id'    => $this->instance->trial_workflow_id,
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
    * Create the workflow unit audit record
    * @throws WorkflowUnitAuditCreateException
    * @return void
    */
   public function commitChanges(): void
   {
      foreach ($this->auditRecordData as $recordData) {
         if (! TrialWorkflowUnitAudit::create($recordData)) {
            throw new WorkflowUnitAuditCreateException;
         }
      }
   }
}
