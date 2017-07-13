<?php

namespace Rareloop\Router;

use Rareloop\Router\Routable;
use Rareloop\Router\VerbShortcutsTrait;

class RouteGroup implements Routable
{
    use VerbShortcutsTrait;

    protected $router;
    protected $prefix;

    public function __construct(string $prefix, $router)
    {
        $this->prefix = trim($prefix, ' /');
        $this->router = $router;
    }

    private function appendPrefixToUri(string $uri)
    {
        return $this->prefix . '/' . $uri;
    }

    public function map(array $verbs, string $uri, $callback) : Route
    {
        return $this->router->map($verbs, $this->appendPrefixToUri($uri), $callback);
    }

    public function group($prefix, $callback) : RouteGroup
    {
        $group = new RouteGroup($this->appendPrefixToUri($prefix), $this->router);

        call_user_func($callback, $group);

        return $this;
    }
}
