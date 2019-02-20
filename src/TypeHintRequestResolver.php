<?php

namespace Rareloop\Router;

use Invoker\ParameterResolver\ParameterResolver;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunctionAbstract;
use Zend\Diactoros\ServerRequest;

class TypeHintRequestResolver implements ParameterResolver
{
    private $request;

    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ) {
        if (!isset($this->request)) {
            return $resolvedParameters;
        }
        $parameters = $reflection->getParameters();

        // Skip parameters already resolved
        if (!empty($resolvedParameters)) {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            $parameterClass = $parameter->getClass();

            if (!$parameterClass) {
                continue;
            }

            if ($parameterClass->implementsInterface(ServerRequestInterface::class)) {
                $resolvedParameters[$index] = $this->createRequestOfType($parameterClass);
            }
        }

        return $resolvedParameters;
    }

    private function createRequestOfType(\ReflectionClass $requestClass)
    {
        return $requestClass->newInstance(
            $this->request->getServerParams(),
            $this->request->getUploadedFiles(),
            $this->request->getUri(),
            $this->request->getMethod(),
            $this->request->getBody(),
            $this->request->getHeaders(),
            $this->request->getCookieParams(),
            $this->request->getQueryParams(),
            $this->request->getParsedBody(),
            $this->request->getProtocolVersion()
        );
    }

    public function setRequest(ServerRequest $request)
    {
        $this->request = $request;
    }
}
