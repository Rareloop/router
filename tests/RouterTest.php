<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Rareloop\Router\Exceptions\NamedRouteNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringControllerNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringMethodNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringParseException;
use Rareloop\Router\Exceptions\TooLateToAddNewRouteException;
use Rareloop\Router\Route;
use Rareloop\Router\RouteParams;
use Rareloop\Router\Router;

class RouterTest extends TestCase
{
    /** @test */
    public function map_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->map(['GET'], 'test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('test/123', $route->getUri());
    }

    /** @test */
    public function map_accepts_lowercase_verbs()
    {
        $router = new Router;

        $route = $router->map(['get', 'post', 'put', 'patch', 'delete', 'options'], 'test/123', function () {});

        $this->assertSame(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route->getMethods());
    }

    /** @test */
    public function get_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->get('test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('test/123', $route->getUri());
    }

    /** @test */
    public function post_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->post('test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['POST'], $route->getMethods());
        $this->assertSame('test/123', $route->getUri());
    }

    /** @test */
    public function patch_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->patch('test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['PATCH'], $route->getMethods());
        $this->assertSame('test/123', $route->getUri());
    }

    /** @test */
    public function put_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->put('test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['PUT'], $route->getMethods());
        $this->assertSame('test/123', $route->getUri());
    }

    /** @test */
    public function delete_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->delete('test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['DELETE'], $route->getMethods());
        $this->assertSame('test/123', $route->getUri());
    }

    /** @test */
    public function options_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->options('test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['OPTIONS'], $route->getMethods());
        $this->assertSame('test/123', $route->getUri());
    }

    /** @test */
    public function match_works_with_a_closure()
    {
        $router = new Router;
        $count = 0;

        $route = $router->get('test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match('test/123', 'GET');

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response);
    }

    /** @test */
    public function match_works_with_a_class_and_method_string()
    {
        $router = new Router;

        $route = $router->get('test/123', 'Rareloop\Router\Test\Controllers\TestController@returnHelloWorld');
        $response = $router->match('test/123', 'GET');

        $this->assertSame('Hello World', $response);
    }

    /** @test */
    public function match_throws_exception_with_invalid_class_and_string_method()
    {
        $this->expectException(RouteClassStringParseException::class);

        $router = new Router;

        $route = $router->get('test/123', 'Rareloop\Router\Test\Controllers\TestController:returnHelloWorld');
    }

    /** @test */
    public function match_throws_exception_when_class_and_string_method_contains_an_unfound_class()
    {
        $this->expectException(RouteClassStringControllerNotFoundException::class);

        $router = new Router;

        $route = $router->get('test/123', 'Rareloop\Router\Test\Controllers\UndefinedController@returnHelloWorld');
    }

    /** @test */
    public function match_throws_exception_when_class_and_string_method_contains_an_unfound_method()
    {
        $this->expectException(RouteClassStringMethodNotFoundException::class);

        $router = new Router;

        $route = $router->get('test/123', 'Rareloop\Router\Test\Controllers\TestController@undefinedMethod');
    }

    /** @test */
    public function params_are_parsed_and_passed_into_callback_function()
    {
        $router = new Router;

        $route = $router->get('posts/{postId}/comments/{commentId}', function ($params) use (&$count) {
            $count++;

            $this->assertInstanceOf(RouteParams::class, $params);
            $this->assertSame('123', $params->postId);
            $this->assertSame('abc', $params->commentId);
        });
        $router->match('posts/123/comments/abc', 'GET');

        $this->assertSame(1, $count);
    }

    /** @test */
    public function params_are_parsed_and_passed_into_callback_function_when_surrounded_by_whitespace()
    {
        $router = new Router;

        $route = $router->get('posts/{ postId }/comments/{ commentId }', function ($params) use (&$count) {
            $count++;

            $this->assertInstanceOf(RouteParams::class, $params);
            $this->assertSame('123', $params->postId);
            $this->assertSame('abc', $params->commentId);
        });
        $router->match('posts/123/comments/abc', 'GET');

        $this->assertSame(1, $count);
    }

    /** @test */
    public function can_generate_url_for_named_route()
    {
        $router = new Router;

        $route = $router->get('posts/all', function () {})->name('test.name');

        $this->assertSame('posts/all', $router->url('test.name'));
    }

    /** @test */
    public function generating_a_url_for_a_named_route_that_doesnt_exist_throws_an_exception()
    {
        $this->expectException(NamedRouteNotFoundException::class);

        $router = new Router;

        $router->url('test.name');
    }

    /** @test */
    public function adding_routes_after_calling_url_throws_an_exception()
    {
        $this->expectException(TooLateToAddNewRouteException::class);

        $router = new Router;

        $route = $router->get('posts/all', function () {})->name('test.name');
        $router->url('test.name');

        $route = $router->get('another/url', function () {});
    }

    /** @test */
    public function adding_routes_after_calling_match_throws_an_exception()
    {
        $this->expectException(TooLateToAddNewRouteException::class);

        $router = new Router;

        $route = $router->get('posts/all', function () {});
        $router->match('posts/all');

        $route = $router->get('another/url', function () {});
    }
}
