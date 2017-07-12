<?php

namespace Rareloop\Router;

trait VerbShortcutsTrait
{
    public function get(string $uri, $callback) : Route
    {
        return $this->map(['GET'], $uri, $callback);
    }

    public function post(string $uri, $callback) : Route
    {
        return $this->map(['POST'], $uri, $callback);
    }

    public function patch(string $uri, $callback) : Route
    {
        return $this->map(['PATCH'], $uri, $callback);
    }

    public function put(string $uri, $callback) : Route
    {
        return $this->map(['PUT'], $uri, $callback);
    }

    public function delete(string $uri, $callback) : Route
    {
        return $this->map(['DELETE'], $uri, $callback);
    }

    public function options(string $uri, $callback) : Route
    {
        return $this->map(['OPTIONS'], $uri, $callback);
    }
}
