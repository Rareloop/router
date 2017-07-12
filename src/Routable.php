<?php

namespace Rareloop\Router;

interface Routable
{
    public function map(array $verbs, string $uri, $callback): Route;

    public function get(string $uri, $callback) : Route;

    public function post(string $uri, $callback) : Route;

    public function patch(string $uri, $callback) : Route;

    public function put(string $uri, $callback) : Route;

    public function delete(string $uri, $callback) : Route;

    public function options(string $uri, $callback) : Route;
}
