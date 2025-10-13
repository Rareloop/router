<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Rareloop\Router\ControllerMiddleware;
use Rareloop\Router\ControllerMiddlewareOptions;
use Rareloop\Router\Test\Middleware\AddHeaderMiddleware;

class ControllerMiddlewareTest extends TestCase
{
    /** @test */
    public function can_retrieve_middleware()
    {
        $middleware = new AddHeaderMiddleware('X-Header', 'testing123');
        $options = new ControllerMiddlewareOptions;

        $controllerMiddleware = new ControllerMiddleware($middleware, $options);

        $this->assertSame($middleware, $controllerMiddleware->middleware());
    }

    /** @test */
    public function can_retrieve_options()
    {
        $middleware = new AddHeaderMiddleware('X-Header', 'testing123');
        $options = new ControllerMiddlewareOptions;

        $controllerMiddleware = new ControllerMiddleware($middleware, $options);

        $this->assertSame($options, $controllerMiddleware->options());
    }
}
