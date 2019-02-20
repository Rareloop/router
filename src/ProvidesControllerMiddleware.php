<?php

namespace Rareloop\Router;

use Rareloop\Router\ControllerMiddlewareOptions;

interface ProvidesControllerMiddleware
{
    public function middleware($middleware) : ControllerMiddlewareOptions;

    public function getControllerMiddleware() : array;
}
