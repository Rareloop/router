<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Rareloop\Router\Exceptions\UnknownMiddlewareAliasException;
use Rareloop\Router\MiddlewareAliasStore;
use Rareloop\Router\Test\Middleware\AddHeaderMiddleware;
use Rareloop\Router\Test\Middleware\SimpleMiddleware;

class MiddlewareAliasStoreTest extends TestCase
{
    /** @test */
    public function can_register_alias()
    {
        $store = new MiddlewareAliasStore;

        $store->register('testalias', SimpleMiddleware::class);

        $this->assertTrue($store->has('testalias'));
    }

    /** @test */
    public function can_resolve_alias()
    {
        $store = new MiddlewareAliasStore;

        $store->register('testalias', SimpleMiddleware::class);

        $this->assertInstanceOf(SimpleMiddleware::class, $store->resolve('testalias'));
    }

    /** @test */
    public function can_resolve_alias_when_using_a_closure()
    {
        $store = new MiddlewareAliasStore;

        $store->register('testalias', function () {
            return new SimpleMiddleware;
        });

        $this->assertInstanceOf(SimpleMiddleware::class, $store->resolve('testalias'));
    }

    /** @test */
    public function resolving_an_unregistered_alias_throws_an_exception()
    {
        $this->expectException(UnknownMiddlewareAliasException::class);

        $store = new MiddlewareAliasStore;

        $this->assertInstanceOf(SimpleMiddleware::class, $store->resolve('testalias'));
    }

    /** @test */
    public function can_resolve_alias_with_params()
    {
        $store = new MiddlewareAliasStore;
        $store->register('paramalias', AddHeaderMiddleware::class);

        $middleware = $store->resolve('paramalias:X-Key,X-Value');

        $this->assertInstanceOf(AddHeaderMiddleware::class, $middleware);
        $this->assertSame('X-Key', $middleware->key);
        $this->assertSame('X-Value', $middleware->value);
    }

    /** @test */
    public function can_resolve_alias_with_params_via_closure()
    {
        $count = 0;
        $store = new MiddlewareAliasStore;
        $store->register('paramalias', function ($key, $value) use (&$count) {
            $count++;
            return new AddHeaderMiddleware($key, $value);
        });

        $middleware = $store->resolve('paramalias:X-Key,X-Value');

        $this->assertSame(1, $count);
        $this->assertInstanceOf(AddHeaderMiddleware::class, $middleware);
        $this->assertSame('X-Key', $middleware->key);
        $this->assertSame('X-Value', $middleware->value);
    }
}
