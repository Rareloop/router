<?php

namespace Rareloop\Router;

use ReflectionClass;
use ReflectionFunctionAbstract;
use Zend\Diactoros\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Invoker\ParameterResolver\ParameterResolver;

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
            $parameterClass = $parameter->getType() && !$parameter->getType()->isBuiltin()
                ? new ReflectionClass($parameter->getType()->getName())
                : null;

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
