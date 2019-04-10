<?php

namespace Rareloop\Router\Test;

use Mockery;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Rareloop\Router\Controller;
use Rareloop\Router\ControllerMiddlewareOptions;
use Rareloop\Router\MiddlewareResolver;
use Rareloop\Router\Router;
use Rareloop\Router\Test\Controllers\MiddlewareProvidingController;
use Rareloop\Router\Test\Middleware\AddHeaderMiddleware;
use Zend\Diactoros\ServerRequest;

class ControllerTest extends TestCase
{
    /** @test */
    public function can_add_single_middleware_via_controller()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router($container);

        $controller = new MiddlewareProvidingController;
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'));
        $container->set(MiddlewareProvidingController::class, $controller);

        $router->get(
            '/test/123',
            'Rareloop\Router\Test\Controllers\MiddlewareProvidingController@returnOne'
        );

        $response = $router->match($request);

        $this->assertTrue($response->hasHeader('X-Header'));
        $this->assertSame('testing123', $response->getHeader('X-Header')[0]);
    }

    /** @test */
    public function can_resolve_middleware_on_a_controller_using_custom_resolver()
    {
        $container = ContainerBuilder::buildDevContainer();
        $resolver = $this->createMockMiddlewareResolverWithHeader('X-Header', 'testing123');
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router($container, $resolver);

        $controller = new MiddlewareProvidingController;
        $controller->middleware('middleware-key');
        $container->set(MiddlewareProvidingController::class, $controller);

        $router->get(
            '/test/123',
            'Rareloop\Router\Test\Controllers\MiddlewareProvidingController@returnOne'
        );

        $response = $router->match($request);

        $this->assertTrue($response->hasHeader('X-Header'));
        $this->assertSame('testing123', $response->getHeader('X-Header')[0]);
    }

    /** @test */
    public function can_add_multiple_middleware_as_array_via_controller()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router($container);

        $controller = new MiddlewareProvidingController;
        $controller->middleware([
            new AddHeaderMiddleware('X-Header-1', 'testing123'),
            new AddHeaderMiddleware('X-Header-2', 'testing456')
        ]);
        $container->set(MiddlewareProvidingController::class, $controller);

        $router->get(
            '/test/123',
            'Rareloop\Router\Test\Controllers\MiddlewareProvidingController@returnOne'
        );

        $response = $router->match($request);

        $this->assertTrue($response->hasHeader('X-Header-1'));
        $this->assertSame('testing123', $response->getHeader('X-Header-1')[0]);
        $this->assertTrue($response->hasHeader('X-Header-2'));
        $this->assertSame('testing456', $response->getHeader('X-Header-2')[0]);
    }

    /** @test */
    public function controller_middleware_method_returns_options()
    {
        $controller = new MiddlewareProvidingController;

        $options = $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'));

        $this->assertInstanceOf(ControllerMiddlewareOptions::class, $options);
    }

    /** @test */
    public function middleware_can_be_limited_to_methods_using_only()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);

        $controller = new MiddlewareProvidingController;
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->only('returnOne');
        $container->set(MiddlewareProvidingController::class, $controller);

        $middlewareAppliedToMethods = [
            'returnOne' => true,
            'returnTwo' => false,
            'returnThree' => false,
        ];

        $this->assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods);
    }

    /** @test */
    public function middleware_can_be_limited_to_multiple_methods_using_only()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);

        $controller = new MiddlewareProvidingController;
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->only(['returnOne', 'returnThree']);
        $container->set(MiddlewareProvidingController::class, $controller);

        $middlewareAppliedToMethods = [
            'returnOne' => true,
            'returnTwo' => false,
            'returnThree' => true,
        ];

        $this->assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods);
    }

    /** @test */
    public function middleware_can_be_limited_to_methods_using_except()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);

        $controller = new MiddlewareProvidingController;
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->except('returnOne');
        $container->set(MiddlewareProvidingController::class, $controller);

        $middlewareAppliedToMethods = [
            'returnOne' => false,
            'returnTwo' => true,
            'returnThree' => true,
        ];

        $this->assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods);
    }

    /** @test */
    public function middleware_can_be_limited_to_multiple_methods_using_except()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);

        $controller = new MiddlewareProvidingController;
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->except(['returnOne', 'returnThree']);
        $container->set(MiddlewareProvidingController::class, $controller);

        $middlewareAppliedToMethods = [
            'returnOne' => false,
            'returnTwo' => true,
            'returnThree' => false,
        ];

        $this->assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods);
    }

    protected function assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods)
    {
        // Add the routes
        foreach ($middlewareAppliedToMethods as $method => $applied) {
            $router->get(
                '/test/' . $method,
                'Rareloop\Router\Test\Controllers\MiddlewareProvidingController@' . $method
            );
        }

        // Test middleware is only applied to the correct routes
        foreach ($middlewareAppliedToMethods as $method => $applied) {
            $response = $router->match(new ServerRequest([], [], '/test/' . $method, 'GET'));

            if ($applied) {
                $this->assertTrue($response->hasHeader('X-Header'), '`'.$method.'` should have middleware applied');
                $this->assertSame('testing123', $response->getHeader('X-Header')[0]);
            } else {
                $this->assertFalse($response->hasHeader('X-Header'), '`'.$method.'` should not have middleware applied');
            }
        }
    }

    private function createMockMiddlewareResolverWithHeader($header, $value)
    {
        $middleware = new AddHeaderMiddleware($header, $value);
        $resolver = Mockery::mock(MiddlewareResolver::class);
        $resolver->shouldReceive('resolve')->with('middleware-key')->andReturn($middleware);
        $resolver->shouldReceive('resolve')->with(Mockery::type('callable'))->andReturnUsing(function ($argument) {
            return $argument;
        });

        return $resolver;
    }
}
