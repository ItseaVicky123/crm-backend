<?php


namespace App\Lib\ModuleHandlers\Interfaces;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * Interface ModuleRequestInterface
 * @package App\Lib\ModuleHandlers\Interfaces
 */
interface ModuleRequestInterface
{
    /**
     * Validate the module request data.
     * @param array $rules
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function validate(array $rules): void;
}
