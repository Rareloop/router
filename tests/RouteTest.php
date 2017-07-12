<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Rareloop\Router\Exceptions\RouteNameRedefinedException;
use Rareloop\Router\Route;
use Rareloop\Router\Router;

class RouteTest extends TestCase
{
    /** @test */
    public function a_route_can_be_named()
    {
        $router = new Router;

        $this->assertFalse($router->has('test'));
        $route = $router->get('test/123', function () {})->name('test');
        $this->assertTrue($router->has('test'));
    }

    /** @test */
    public function name_function_is_chainable()
    {
        $router = new Router;

        $this->assertInstanceOf(Route::class, $router->get('test/123', function () {})->name('test'));
    }

    /** @test */
    public function a_route_can_not_be_renamed()
    {
        $this->expectException(RouteNameRedefinedException::class);

        $router = new Router;

        $route = $router->get('test/123', function () {})->name('test1')->name('test2');
    }
}
