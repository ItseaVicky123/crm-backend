<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\Response;

trait TestsApi
{

    protected function requestHasSucceeded()
    {
        try {
            $this
                ->seeStatusCode(Response::HTTP_OK)
                ->seeJson(['status' => 'SUCCESS']);

            return $this;
        } catch (\Exception $e) {
            $request = $this->app['request'];
            \fileLogger::debug([
                'class'   => get_called_class() . '::' . $this->getName(),
                'destination' => $request->getMethod() . ' ' . $request->getRequestUri(),
                'request' => json_decode($request->getContent(), true),
                'response' => json_decode($this->response->getContent(), true)
            ]);
            throw $e;
        }
    }

    /**
     * @param int $code
     * @return self
     */
    protected function responseCodeEquals(int $code): self
    {
        $response = json_decode($this->response->getContent());

        self::assertEquals($code, $response->response_code, $response->response_message ?? $response->error_message ?? '');

        return $this;
    }

    protected function requestHasFailed($code)
    {
        $this
            ->seeStatusCode($code)
            ->seeJson([
                'status' => 'FAILURE',
            ]);

        return $this;
    }

    protected function _cantTest($method, $reason)
    {
        $this->assertTrue(true, sprintf(
            'Unable to test [%s]. %s',
            $method,
            $reason
        ));
    }

    protected function v1HasSuccessed()
    {
        $this
            ->seeStatusCode(Response::HTTP_OK)
            ->seeJson([
                'response_code' => '100',
            ]);

        return $this;
    }
}
