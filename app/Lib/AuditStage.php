<?php

namespace App\Lib;
use Illuminate\Database\Eloquent\Model;
use App\Lib\Interfaces\AuditStageInterface;

/**
 * Class AuditStage
 * @package App\Lib
 * Base class for Audit table generating modules
 */
class AuditStage implements AuditStageInterface
{
   /**
    * @var array $updates
    */
   protected array $updates = [];

   /**
    * @var Model $instance
    */
   protected Model $instance;

   /**
    * @var array $auditRecordData
    */
   protected array $auditRecordData = [];

   /**
    * Tells module class what field names to iterate through
    * @var array $flatFieldNames
    */
   protected array $flatFieldNames = [];

   /**
    * Tells module class what fields to format as Yes/No for 0/1 values
    * @var array $formatYesNo
    */
   protected array $formatYesNo = [];

   /**
    * Tells module class what fields to format as Active/Inactive for 0/1 values
    * @var array $formatActiveInactive
    */
   protected array $formatActiveInactive = [];
   /**
    * Tells module class what fields to format in a custom specification
    * @var array $formatActiveInactive
    */
   protected array $formatCustomMap = [];

   /**
    * @param Model $instance
    * @return AuditStage
    */
   public function setInstance(Model &$instance): AuditStage
   {
      $this->instance = $instance;

      return $this;
   }

   /**
    * @param array $updates
    * @return AuditStage
    */
   public function setUpdates(array &$updates): AuditStage
   {
      $this->updates = $updates;

      return $this;
   }

   /**
    * Determine if update key exists, the value can be null
    * Make sure the instance has $key visible and accessible
    * @param string $key
    * @return bool
    */
   protected function updateRequestedFor(string $key): bool
   {
      return (
         array_key_exists($key, $this->updates) &&
         $this->instance->{$key} != $this->updates[$key]
      );
   }

   /**
    * Autoformat key names, overridable
    * @param string $key
    * @return string
    */
   protected function formatFieldName(string $key): string
   {
      return ucfirst(str_replace('_', ' ', $key));
   }

   /**
    * Autoformat values
    * @param string $key
    * @param mixed $value
    * @return string
    */
   protected function formatFieldValue(string $key, $value): string
   {
      $formatted = $value;

      if (in_array($key, $this->formatYesNo)) {
         $formatted = ($value == 1 ? 'Yes' : 'No');
      } else if (in_array($key, $this->formatActiveInactive, true)) {
         $formatted = ($value == 1 ? 'Active' : 'Inactive');
      } else if (isset($this->formatCustomMap[$key]) && isset($this->formatCustomMap[$key][$value])) {
         $formatted = $this->formatCustomMap[$key][$value];
      }

      return $formatted;
   }

   /**
    * Add to the audit record array
    * @param array $recordParams
    */
   protected function appendAuditRecordChanges(array $recordParams): void
   {
      $this->auditRecordData[] = $recordParams;
   }
}
