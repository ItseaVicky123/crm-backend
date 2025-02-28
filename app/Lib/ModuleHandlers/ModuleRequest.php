<?php

namespace App\Lib\ModuleHandlers;

use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use App\Lib\ModuleHandlers\Interfaces\ModuleRequestInterface;
use App\Exceptions\ModuleHandlers\ModuleRequestPropertyException;

/**
 * Container for data moving between modules.
 * Class ModuleRequest
 * @package App\Lib\ModuleHandlers
 */
class ModuleRequest extends Collection implements ModuleRequestInterface
{
    /**
     * ModuleRequest constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Validate the module request data base implementation.
     * @param array $rules
     * @param array $attributeNames
     * @param array $messages
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function validate(array $rules, array $attributeNames = [], array $messages = []): void
    {
        /**
         * @var Validator $validator
         */
        $validator = ValidatorFacade::make($this->all(), $rules, $messages);

        if ($attributeNames) {
            $validator->setAttributeNames($attributeNames);
        }

        $validator->validate();
    }

    /**
     * Append a value to the module request list.
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function append(string $key, $value): self
    {
        $this->put($key, $value);

        return $this;
    }

    /**
     * Dynamically call properties from collection data.
     * @param $name
     * @return mixed
     * @throws ModuleRequestPropertyException
     */
    public function __get($name)
    {
        if ($this->has($name)) {
            return $this->get($name);
        } else {
            throw new ModuleRequestPropertyException($name, __METHOD__);
        }
    }

    /**
     * Convert Illuminate request to module request.
     * @param Request $request
     * @param array $additional
     * @return static
     */
    public static function createFromApiRequest(Request $request, array $additional = []): self
    {
        $requestData = $request->all();

        if (! $requestData) {
            $requestData = [];
        }

        return new static(array_merge($requestData, $additional));
    }

    /**
     * Get an array of values where they exist based upon the list of keys.
     * @param array $list
     * @return array
     */
    public function getWhereExists(array $list): array
    {
        $data = [];

        foreach ($list as $key) {
            if ($this->has($key)) {
                $data[$key] = $this->get($key);
            }
        }

        return $data;
    }

    /**
     * Fetch a collection item as a collection.
     * @param string $key
     * @return Collection|null
     */
    public function getAsCollection(string $key): ?Collection
    {
        $collection = null;

        if ($this->has($key)) {
            $collection = collect($this->get($key));
        }

        return $collection;
    }
}
