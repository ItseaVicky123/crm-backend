<?php

namespace App\Lib\ModuleHandlers;

use App\Exceptions\ModuleHandlers\ModuleHandlerException;
use App\Lib\ModuleHandlers\Interfaces\ModuleHandlerInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Module handler base class. Single responsibility logical wrappers for internal implementations
 * Class ModuleHandler
 * @package App\Lib
 */
class ModuleHandler implements ModuleHandlerInterface
{
    /**
     * The data resource for this module handler.
     * @var Model|null $resource
     */
    protected ?Model $resource = null;

    /**
     * The data ID for this module handler.
     * @var int|null $resourceId
     */
    protected ?int $resourceId = null;

    /**
     * Validation rules that will go into Illuminate/Validation/Validator.
     * @var array $validationRules
     */
    protected array $validationRules = [];

    /**
     * @var array $friendlyAttributeNames
     */
    protected array $friendlyAttributeNames = [];

    /**
     * @var ModuleRequest $moduleRequest
     */
    protected ModuleRequest $moduleRequest;

    /**
     * @var bool $isUpdateExisting
     */
    protected bool $isUpdateExisting = false;

    /**
     * @var \Exception|null $exception
     */
    protected ?\Exception $exception = null;

    /**
     * ModuleHandler constructor.
     * @param ModuleRequest $moduleRequest
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function __construct(ModuleRequest $moduleRequest)
    {
        $this->moduleRequest = $moduleRequest;

        // Give extensions a hook to modify static validation rules if needed
        //
        $this->beforeValidation();

        if ($this->validationRules) {
            $this->moduleRequest->validate(
                $this->validationRules,
                $this->friendlyAttributeNames
            );
        }

        // Give extensions a hook to perform more validation after data has been validated
        //
        $this->afterValidation();
    }

    /**
     * Core handler action stub function.
     * @throws ModuleHandlerException
     */
    public function performAction(): void
    {
        throw new ModuleHandlerException(__METHOD__, 'performAction is not implemented');
    }

    /**
     * Get the data resource instance.
     * @return Model|null
     */
    public function getResource(): ?Model
    {
        return $this->resource;
    }

    /**
     * Get the data ID of the resource instance.
     * @return int|null
     */
    public function getResourceId(): ?int
    {
        return $this->resourceId;
    }

    /**
     * @return \Exception|null
     */
    public function getException(): ?\Exception
    {
        return $this->exception;
    }

    /**
     * @param \Exception $e
     */
    public function setException(\Exception $e)
    {
        $this->exception = $e;
    }

    /**
     * Initializes validation rules if needed.
     */
    protected function beforeValidation(): void
    {
    }

    /**
     * Add post-data validation checks that may include more elaborate conditions.
     */
    protected function afterValidation(): void
    {
    }

    /**
     * @return bool
     */
    public function hasException(): bool
    {
        return !is_null($this->exception);
    }
}
