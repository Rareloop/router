<?php

namespace Rareloop\Router\Test\Middleware;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\ServerRequest;

class SimpleMiddleware implements MiddlewareInterface
{
    public function process(RequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);

        return $response->withHeader('X-Test-Header', 'testing123');
    }
}
