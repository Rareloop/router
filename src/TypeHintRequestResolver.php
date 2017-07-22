<?php

namespace Rareloop\Router;

use Invoker\ParameterResolver\ParameterResolver;
use ReflectionFunctionAbstract;
use Symfony\Component\HttpFoundation\Request;

class TypeHintRequestResolver implements ParameterResolver
{
    private $request;

    public function getParameters(ReflectionFunctionAbstract $reflection, array $providedParameters, array $resolvedParameters) {
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

            if ($parameterClass->name === Request::class) {
                $resolvedParameters[Request::class] = $this->request;
            } elseif ($parameterClass->isSubclassOf(Request::class)) {
                $resolvedParameters[Request::class] = $this->createRequestOfType($parameterClass);
            }
        }

        return $resolvedParameters;
    }

    private function createRequestOfType(\ReflectionClass $requestClass)
    {
        $createMethod = $requestClass->getMethod('create');

        return $createMethod->invoke(
            null,
            $this->request->getRequestUri(),
            $this->request->getMethod(),
            $this->request->request->all(),
            $this->request->cookies->all(),
            $this->request->files->all(),
            $this->request->server->all(),
            $this->request->getContent()
        );
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
}
