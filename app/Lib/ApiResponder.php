<?php

namespace App\Lib;

use App\Providers\MicroServiceProviderResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Transformers\ApiResponse;
use App\Lib\ModuleHandlers\Utilities\ValidationExceptionFormatter;
use Illuminate\Validation\ValidationException;

/**
 * Trait ApiResponder
 * @package App\Lib
 */
trait ApiResponder
{

    /**
     * @param array|Collection|Model|null $data
     * @param array                       $also_visible
     * @return MicroServiceProviderResponse|array|Builder[]|\Illuminate\Database\Eloquent\Collection|Model|Collection|null
     */
    private function parseResponseData($data, array $also_visible = [])
    {
        if ($data instanceof Model || $data instanceof Collection) {
            $data = $data;
        } elseif ($data instanceof Builder) {
            $data = $data->get();
        } elseif ($data instanceof MicroServiceProviderResponse) {
            if ($data->isValid()) {
                $data = ((array) $data->getData() ?: []);
            }
        } elseif (is_null($data)) {
            return null;
        }

        if ($also_visible) {
            $data->makeVisible($also_visible);
        }

        return is_array($data)
            ? $data
            : $data->toArray();
    }

    protected function response($data = [], array $also_visible = [], $message = '')
    {
        return (new ApiResponse)->success($message, $this->parseResponseData($data, $also_visible));
    }

    protected function abort($http_code = 400, $message = '', $data = [], $resp_code = null)
    {
        return (new ApiResponse($http_code))->failure($message, $data, $resp_code);
    }

    protected function responseWithCode($key, $data = [])
    {
        return (new ApiResponse)->success(__("code.{$key}.message"), $this->parseResponseData($data), (int) __("code.{$key}.code"));
    }

    protected function abortWithCode($http_code = 400, $key, $data = [], $tokens = [])
    {
        return (new ApiResponse($http_code))->failure(__("code.{$key}.message", $tokens), $this->parseResponseData($data), (int) __("code.{$key}.code"));
    }

    /**
     * @param ValidationException $e
     * @return JsonResponse
     */
    protected function abortWithValidatorException(ValidationException $e): JsonResponse
    {
        $formatter = new ValidationExceptionFormatter($e);

        return $this->abort(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $formatter->first(),
            ['errors' => $formatter->getFlatMessages()]
        );
    }
}
