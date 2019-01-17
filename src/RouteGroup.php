<?php

namespace Rareloop\Router;

use Rareloop\Router\Routable;
use Rareloop\Router\VerbShortcutsTrait;
use Spatie\Macroable\Macroable;

class RouteGroup implements Routable
{
    use VerbShortcutsTrait, Macroable;

    protected $router;
    protected $prefix;
    protected $middleware = [];

    public function __construct($params, $router)
    {
        $prefix = null;
        $middleware = [];

        if (is_string($params)) {
            $prefix = $params;
        }

        if (is_array($params)) {
            $prefix = $params['prefix'] ?? null;
            $middleware = $params['middleware'] ?? [];

            if (!is_array($middleware)) {
                $middleware = [$middleware];
            }

            $this->middleware += $middleware;
        }

        $this->prefix = trim($prefix, ' /');
        $this->router = $router;
    }

    private function appendPrefixToUri(string $uri)
    {
        return $this->prefix . '/' . ltrim($uri, '/');
    }

    public function map(array $verbs, string $uri, $callback) : Route
    {
        return $this->router->map($verbs, $this->appendPrefixToUri($uri), $callback)->middleware($this->middleware);
    }

    public function group($params, $callback) : RouteGroup
    {
        if (is_string($params)) {
            $params = $this->appendPrefixToUri($params);
        } elseif (is_array($params)) {
            $params['prefix'] = $params['prefix'] ? $this->appendPrefixToUri($params['prefix']) : null;
        }

        $group = new RouteGroup($params, $this->router);

        call_user_func($callback, $group);

        return $this;
    }
}
