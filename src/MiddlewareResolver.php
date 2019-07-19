<?php

namespace Rareloop\Router;

interface MiddlewareResolver
{
    /**
     * Resolves a middleware
     *
     * @param  mixed $name The key to lookup a middleware
     * @return mixed
     */
    public function resolve($name);
}
