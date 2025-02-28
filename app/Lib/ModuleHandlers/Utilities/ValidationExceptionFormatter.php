<?php


namespace App\Lib\ModuleHandlers\Utilities;

use Illuminate\Validation\ValidationException;

/**
 * Class ValidationExceptionFormatter
 * @package App\Lib\ModuleHandlers\Utilities
 */
class ValidationExceptionFormatter
{
    /**
     * Error messages in a flat array.
     * @var array $flatMessages
     */
    protected array $flatMessages = [];

    /**
     * The number of error messages total.
     * @var int $errorCount
     */
    protected int $errorCount = 0;

    public function __construct(ValidationException $e)
    {
        if ($errors = $e->errors()) {
            foreach ($errors as $key => $errorData) {
                foreach ($errorData as $errorMessage) {
                    $this->flatMessages[] = $errorMessage;
                    $this->errorCount++;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getFlatMessages(): array
    {
        return $this->flatMessages;
    }

    /**
     * @return int
     */
    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    /**
     * @param bool $withNumericPrefix
     * @param string $delimiter
     * @return string
     */
    public function getFullErrorMessage(bool $withNumericPrefix = false, string $delimiter = ' '): string
    {
        $message = 'Unknown error';

        if ($this->getErrorCount() > 0) {
            if (!$withNumericPrefix) {
                $message = implode($delimiter, $this->getFlatMessages());
            } else {
                $formattedErrors = [];

                foreach ($this->getFlatMessages() as $i => $message) {
                    $number = $i + 1;
                    $formattedErrors[] = "({$number}) {$message}";
                }

                $message = implode($delimiter, $formattedErrors);
            }
        }

        return $message;
    }

    /**
     * Get first message.
     * @return mixed|string
     */
    public function first(): string
    {
        if (isset($this->flatMessages[0])) {
            return $this->flatMessages[0];
        }

        return '';
    }
}