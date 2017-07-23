<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Router\Route;
use Rareloop\Router\Router;
use Rareloop\Router\Test\Middleware\AddHeaderMiddleware;
use Zend\Diactoros\ServerRequest;

class RouterMiddlewareTest extends TestCase
{
    /** @test */
    public function can_add_middleware_as_a_closure_to_a_route()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        })->middleware(function (ServerRequestInterface $request, callable $next) use (&$count) {
            $count++;

            $response = $next($request);
            return $response->withHeader('X-Key', 'value');
        });
        $response = $router->match($request);

        $this->assertSame(2, $count);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertTrue($response->hasHeader('X-Key'));
        $this->assertSame('value', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function can_add_middleware_as_an_object_to_a_route()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        })->middleware(new AddHeaderMiddleware('X-Key', 'value'));
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('X-Key'));
        $this->assertSame('value', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function can_add_multiple_middleware_to_a_route_in_successive_calls()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;

        $router->get('/test/123', function () {})
            ->middleware(new AddHeaderMiddleware('X-Key1', 'abc'))
            ->middleware(new AddHeaderMiddleware('X-Key2', '123'));

        $response = $router->match($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('X-Key1'));
        $this->assertTrue($response->hasHeader('X-Key2'));
        $this->assertSame('abc', $response->getHeader('X-Key1')[0]);
        $this->assertSame('123', $response->getHeader('X-Key2')[0]);
    }

    /** @test */
    public function can_add_multiple_middleware_to_a_route_in_a_single_call()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;

        $router->get('/test/123', function () {})->middleware(
            new AddHeaderMiddleware('X-Key1', 'abc'),
            new AddHeaderMiddleware('X-Key2', '123')
        );

        $response = $router->match($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('X-Key1'));
        $this->assertTrue($response->hasHeader('X-Key2'));
        $this->assertSame('abc', $response->getHeader('X-Key1')[0]);
        $this->assertSame('123', $response->getHeader('X-Key2')[0]);
    }
}
