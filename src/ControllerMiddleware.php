<?php

namespace Rareloop\Router;

use Psr\Http\Server\MiddlewareInterface;
use Rareloop\Router\ControllerMiddlewareOptions;

class ControllerMiddleware
{
    /**
     * @var Psr\Http\Server\MiddlewareInterface
     */
    protected $middleware;

    /**
     * @var Rareloop\Router\ControllerMiddlewareOptions
     */
    protected $options;

    /**
     * Constructor
     *
     * @param Psr\Http\Server\MiddlewareInterface|array $middleware
     * @param Rareloop\Router\ControllerMiddlewareOptions $options
     */
    public function __construct($middleware, ControllerMiddlewareOptions $options)
    {
        $this->middleware = $middleware;
        $this->options = $options;
    }

    /**
     * Get the Middleware
     *
     * @return Psr\Http\Server\MiddlewareInterface|string
     */
    public function middleware()
    {
        return $this->middleware;
    }

    /**
     * Get the ControllerMiddlewareOptions
     *
     * @return Rareloop\Router\ControllerMiddlewareOptions
     */
    public function options() : ControllerMiddlewareOptions
    {
        return $this->options;
    }

    /**
     * Is a specific method excluded by the options set on this object
     *
     * @param  string $method
     * @return bool
     */
    public function excludedForMethod($method) : bool
    {
        return $this->options->excludedForMethod($method);
    }
}
