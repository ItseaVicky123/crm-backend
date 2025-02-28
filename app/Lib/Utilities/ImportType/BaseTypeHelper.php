<?php


namespace App\Lib\Utilities\ImportType;


use array_accessor;
use Exception;
use App\Lib\ApiResponder;
use Laravel\Lumen\Routing\ProvidesConvenienceMethods;
use rule_exception;
use rule_validator;


/**
 * Class BaseTypeHelper
 * @package App\Lib\Utilities\ImportType
 */
abstract class BaseTypeHelper
{
    use ApiResponder, ProvidesConvenienceMethods;

    /**
     * @var array
     */
    public array $lastRow;

    /**
     * @var array[]
     */
    public array $record = [];

    /**
     * @var array
     */
    public array $commonRules;

    /**
     * @var rule_validator
     */
    public rule_validator $ruleValidator;

    /**
     * BaseTypeHelper constructor.
     */
    public function __construct()
    {
        $this->setRules();
    }

    /**
     * @param $rowData
     * @return bool
     */
    public function validateImportRow($rowData): bool
    {
        $valid         = false;
        $errorKey      = '';
        $mappedRow     = array_combine($this->columnMap, $rowData);
        $errorMessage  = '';
        $statusMessage = 'OK';
        $this->record  = [];

        try {
            $this->validateData($mappedRow);
            $valid = $this->buildRecord(new array_accessor($mappedRow));
        } catch (Exception $e) {
            $errorKey      = $e instanceof rule_exception ? $this->ruleValidator->get_last_attempted_key() : '';
            $errorMessage  = $e->getMessage();
            $statusMessage = "Validation Error: {$errorMessage}";
        }

        $this->buildOutput($mappedRow, $valid, $statusMessage, $errorKey, $errorMessage);

        return $valid;
    }

    /**
     * @param $mappedRow
     * @param $valid
     * @param $statusMessage
     * @param string $errorKey
     * @param string $errorMessage
     */
    protected function buildOutput($mappedRow, $valid, $statusMessage, $errorKey = '', $errorMessage = ''): void
    {
        $this->lastRow = $this->defaultOutput;

        foreach ($mappedRow as $key => $val) {
            if ($errorKey === (string) $key) {
                $this->lastRow[$key] = $errorMessage;
            } else {
                $this->lastRow[$key] = $val;
            }
        }
        $this->lastRow['status_message'] = $statusMessage;
        $this->lastRow['success']        = (int) $valid;
    }

    public function setRules(): void
    {
        $this->commonRules = [
            'required_text' => [
                rule_validator::required(),
                rule_validator::rule_maxlength(255),
            ],
            'money'         => [
                rule_validator::rule_maxlength(14),
                rule_validator::rule_float(),
            ],
            'required_flag' => [
                rule_validator::required(),
                rule_validator::rule_flag(),
            ],
        ];

        $this->ruleValidator = new rule_validator($this->getRules());
    }

    abstract protected function getRules();
    abstract protected function buildRecord($data);
    abstract protected function validateData($data);
    abstract protected function appendPrimaryKey();
    abstract protected function afterSave();
}