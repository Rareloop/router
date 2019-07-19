<?php

namespace Rareloop\Router\Test;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Router\ControllerMiddlewareOptions;
use Rareloop\Router\MiddlewareResolver;
use Rareloop\Router\Route;
use Rareloop\Router\RouteGroup;
use Rareloop\Router\Router;
use Rareloop\Router\Test\Controllers\MiddlewareProvidingController;
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

    /** @test */
    public function can_add_multiple_middleware_to_a_route_as_an_array()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;

        $router->get('/test/123', function () {})->middleware([
            new AddHeaderMiddleware('X-Key1', 'abc'),
            new AddHeaderMiddleware('X-Key2', '123'),
        ]);

        $response = $router->match($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('X-Key1'));
        $this->assertTrue($response->hasHeader('X-Key2'));
        $this->assertSame('abc', $response->getHeader('X-Key1')[0]);
        $this->assertSame('123', $response->getHeader('X-Key2')[0]);
    }

    /** @test */
    public function can_add_middleware_to_a_group()
    {
        $request = new ServerRequest([], [], '/all', 'GET');
        $router = new Router;
        $count = 0;

        $router->group(['middleware' => [new AddHeaderMiddleware('X-Key', 'abc')]], function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader('X-Key'));
        $this->assertSame('abc', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function can_add_single_middleware_to_a_group_without_wrapping_in_array()
    {
        $request = new ServerRequest([], [], '/all', 'GET');
        $router = new Router;
        $count = 0;

        $router->group(['middleware' => new AddHeaderMiddleware('X-Key', 'abc')], function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader('X-Key'));
        $this->assertSame('abc', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function can_add_base_middleware_to_be_applied_to_all_routes()
    {
        $router = new Router;
        $router->setBaseMiddleware([
            new AddHeaderMiddleware('X-Key', 'abc'),
        ]);
        $count = 0;

        $router->get('one', function () use (&$count) {
            $count++;
            return 'abc123';
        });

        $router->get('two', function () use (&$count) {
            $count++;
            return 'abc123';
        });

        $response1 = $router->match(new ServerRequest([], [], '/one', 'GET'));
        $response2 = $router->match(new ServerRequest([], [], '/two', 'GET'));

        $this->assertSame(2, $count);

        $this->assertSame(200, $response1->getStatusCode());
        $this->assertSame('abc123', $response1->getBody()->getContents());
        $this->assertTrue($response1->hasHeader('X-Key'));
        $this->assertSame('abc', $response1->getHeader('X-Key')[0]);

        $this->assertSame(200, $response2->getStatusCode());
        $this->assertSame('abc123', $response2->getBody()->getContents());
        $this->assertTrue($response2->hasHeader('X-Key'));
        $this->assertSame('abc', $response2->getHeader('X-Key')[0]);
    }

    /** @test */
    public function can_resolve_middleware_on_a_route_using_a_custom_resolver()
    {
        $resolver = $this->createMockMiddlewareResolverWithHeader('X-Key', 'abc');
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router(null, $resolver);

        $router->get('/test/123', function () {})->middleware('middleware-key');

        $response = $router->match($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('X-Key'));
        $this->assertSame('abc', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function can_resolve_middleware_on_a_group_using_a_custom_resolver()
    {
        $resolver = $this->createMockMiddlewareResolverWithHeader('X-Key', 'abc');
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router(null, $resolver);

        $router->group(['middleware' => 'middleware-key'], function ($group) {
            $group->get('/test/123', function () {});
        });

        $response = $router->match($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('X-Key'));
        $this->assertSame('abc', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function can_resolve_global_middleware_using_a_custom_resolver()
    {
        $resolver = $this->createMockMiddlewareResolverWithHeader('X-Key', 'abc');
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router(null, $resolver);
        $router->setBaseMiddleware(['middleware-key']);

        $router->get('/test/123', function () {});

        $response = $router->match($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('X-Key'));
        $this->assertSame('abc', $response->getHeader('X-Key')[0]);
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
