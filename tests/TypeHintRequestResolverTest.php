<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Rareloop\Router\TypeHintRequestResolver;
use Zend\Diactoros\ServerRequest;

class TypeHintRequestResolverTest extends TestCase
{
    /** @test */
    public function returns_resolved_parameters_when_no_request_is_set()
    {
        $reflectionFunction = new \ReflectionFunction(function () {});
        $resolvedParameters = ['a' => 123, 'b' => 456];
        $resolver = new TypeHintRequestResolver();

        $params = $resolver->getParameters($reflectionFunction, [], $resolvedParameters);

        $this->assertSame($resolvedParameters, $params);
    }

    /** @test */
    public function can_resolve_a_request()
    {
        $request = new ServerRequest([], [], '/injected', 'GET');
        $reflectionFunction = new \ReflectionFunction(function (ServerRequest $request) {});
        $resolver = new TypeHintRequestResolver();
        $resolver->setRequest($request);

        $params = $resolver->getParameters($reflectionFunction, [], []);

        $this->assertSame('/injected', $params[0]->getUri()->getPath());
    }

    /** @test */
    public function does_not_attempt_to_resolve_params_that_have_already_been_resolved()
    {
        $preResolvedRequest = new ServerRequest([], [], '/pre/resolved', 'GET');
        $injectedRequest = new ServerRequest([], [], '/injected', 'GET');
        $reflectionFunction = new \ReflectionFunction(function (ServerRequest $request) {});
        $resolvedParameters = [0 => $preResolvedRequest];
        $resolver = new TypeHintRequestResolver();

        $resolver->setRequest($injectedRequest);
        $params = $resolver->getParameters($reflectionFunction, [], $resolvedParameters);

        $this->assertSame('/pre/resolved', $params[0]->getUri()->getPath());
    }
}
