<?php

namespace Rareloop\Router;

interface ResolvesMiddleware
{
    /**
     * Resolves a middleware
     *
     * @param  mixed $name The key to lookup a middleware
     * @return mixed
     */
    public function resolve($name);
}
