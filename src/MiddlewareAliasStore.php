<?php

namespace Rareloop\Router;

use Rareloop\Router\Exceptions\UnknownMiddlewareAliasException;

class MiddlewareAliasStore
{
    private $aliases = [];

    public function register($alias, $className)
    {
        $this->aliases[$alias] = $className;
    }

    public function has($alias)
    {
        return isset($this->aliases[$alias]);
    }

    public function resolve($alias)
    {
        @list($alias, $params) = explode(':', $alias);
        $params = explode(',', $params ?? '');

        if (!$this->has($alias)) {
            throw new UnknownMiddlewareAliasException;
        }

        $middleware = $this->aliases[$alias];

        if ($middleware instanceof \Closure) {
            return $middleware(...$params);
        }

        return new $middleware(...$params);
    }
}
