<?php

namespace Rareloop\Router\Test;

use Rareloop\Router\Route;
use Rareloop\Router\Router;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Rareloop\Router\Exceptions\RouteNameRedefinedException;

class RouteTest extends TestCase
{
    #[Test]
    public function a_route_can_be_named()
    {
        $router = new Router;

        $this->assertFalse($router->has('test'));
        $route = $router->get('test/123', function () {})->name('test');
        $this->assertTrue($router->has('test'));
    }

    #[Test]
    public function name_function_is_chainable()
    {
        $router = new Router;

        $this->assertInstanceOf(Route::class, $router->get('test/123', function () {})->name('test'));
    }

    #[Test]
    public function a_route_can_not_be_renamed()
    {
        $this->expectException(RouteNameRedefinedException::class);

        $router = new Router;

        $route = $router->get('test/123', function () {})->name('test1')->name('test2');
    }

    #[Test]
    public function where_function_is_chainable()
    {
        $router = new Router;

        $this->assertInstanceOf(Route::class, $router->get('test/{id}', function () {})->where('id', '[0-9]+'));
    }

    #[Test]
    public function where_function_is_chainable_when_passed_an_array()
    {
        $router = new Router;

        $this->assertInstanceOf(Route::class, $router->get('test/{id}', function () {})->where(['id' => '[0-9]+']));
    }

    #[Test]
    public function where_function_throws_exception_when_no_params_provided()
    {
        $this->expectException(InvalidArgumentException::class);

        $router = new Router;

        $this->assertInstanceOf(Route::class, $router->get('test/{id}', function () {})->where());
    }

    #[Test]
    public function can_get_route_action_name_when_closure()
    {
        $router = new Router;
        $route = $router->get('test/123', function () {});

        $this->assertSame('Closure', $route->getActionName());
    }

    #[Test]
    public function can_get_route_action_name_when_callable()
    {
        $router = new Router;
        $route = $router->get('test/123', [TestCallableController::class, 'testStatic']);

        $this->assertSame(TestCallableController::class . '@testStatic', $route->getActionName());
    }

    #[Test]
    public function can_get_route_action_name_when_callable_instance()
    {
        $router = new Router;
        $controller = new TestCallableController;
        $route = $router->get('test/123', [$controller, 'test']);

        $this->assertSame(TestCallableController::class . '@test', $route->getActionName());
    }

    #[Test]
    public function can_get_route_action_name_when_controller_string()
    {
        $router = new Router;
        $route = $router->get('test/123', TestCallableController::class . '@test');

        $this->assertSame(TestCallableController::class . '@test', $route->getActionName());
    }

    #[Test]
    public function can_extend_post_behaviour_with_macros()
    {
        Route::macro('testFunctionAddedByMacro', function () {
            return 'abc123';
        });

        $queryBuilder = new Route(['GET'], '/test/url', function () {});

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMacro());
        $this->assertSame('abc123', Route::testFunctionAddedByMacro());
    }

    #[Test]
    public function can_extend_post_behaviour_with_mixin()
    {
        Route::mixin(new RouteMixin);

        $queryBuilder = new Route(['GET'], '/test/url', function () {});

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMixin());
    }
}

class TestCallableController
{
    public static function testStatic() {}
    public  function test() {}
}

class RouteMixin
{
    function testFunctionAddedByMixin()
    {
        return function () {
            return 'abc123';
        };
    }
}
