<?php

namespace Rareloop\Router\Test\Middleware;

use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\ServerRequest;

class AddHeaderMiddleware implements MiddlewareInterface
{
    private $key;
    private $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function process(RequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);

        return $response->withHeader($this->key, $this->value);
    }
}
