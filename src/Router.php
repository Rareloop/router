<?php

namespace Rareloop\Router;

use Rareloop\Router\AltoRouter\AltoRouter;
use Rareloop\Router\Exceptions\NamedRouteNotFoundException;
use Rareloop\Router\Exceptions\TooLateToAddNewRouteException;
use Rareloop\Router\Route;
use Rareloop\Router\RouteParams;

class Router
{
    private $routes = [];
    private $altoRouter;
    private $altoRoutesCreated = false;

    public function __construct()
    {
        $this->altoRouter = new AltoRouter();
    }

    private function addRoute(Route $route)
    {
        if ($this->altoRoutesCreated) {
            throw new TooLateToAddNewRouteException();
        }

        $this->routes[] = $route;
    }

    private function convertUriForAltoRouter(string $uri): string
    {
        return preg_replace('/{\s*([a-zA-Z0-9]+)\s*}/s', '[:$1]', $uri);
    }

    public function map(array $verbs, string $uri, $callback): Route
    {
        // Force all verbs to be uppercase
        $verbs = array_map('strtoupper', $verbs);

        $route = new Route($verbs, $uri, $callback);

        $this->addRoute($route);

        return $route;
    }

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

    private function createAltoRoutes()
    {
        $this->altoRoutesCreated = true;

        foreach ($this->routes as $route) {
            $this->altoRouter->map(
                implode('|', $route->getMethods()),
                $this->convertUriForAltoRouter($route->getUri()),
                $route->getAction(),
                $route->getName() ?? null
            );
        }
    }

    public function match(string $uri = null, string $method = null)
    {
        $this->createAltoRoutes();

        $altoRoute = $this->altoRouter->match($uri, $method);

        if (is_callable($altoRoute['target'])) {
            if (isset($altoRoute['params'])) {
                $params = new RouteParams($altoRoute['params']);
                return call_user_func($altoRoute['target'], $params);
            } else {
                return call_user_func($altoRoute['target']);
            }
        }
    }

    public function has(string $name)
    {
        $routes = array_filter($this->routes, function ($route) use ($name) {
            return $route->getName() === $name;
        });

        return count($routes) > 0;
    }

    public function url(string $name, $params = [])
    {
        $this->createAltoRoutes();

        try {
            return $this->altoRouter->generate($name, $params);
        } catch (\Exception $e) {
            throw new NamedRouteNotFoundException($name, null);
        }
    }
}
