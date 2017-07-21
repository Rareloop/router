<?php

namespace Rareloop\Router;

use \AltoRouter;
use Rareloop\Router\Exceptions\NamedRouteNotFoundException;
use Rareloop\Router\Exceptions\TooLateToAddNewRouteException;
use Rareloop\Router\Helpers\Formatting;
use Rareloop\Router\Routable;
use Rareloop\Router\Route;
use Rareloop\Router\RouteGroup;
use Rareloop\Router\RouteParams;
use Rareloop\Router\VerbShortcutsTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router implements Routable
{
    use VerbShortcutsTrait;

    private $routes = [];
    private $altoRouter;
    private $altoRoutesCreated = false;
    private $basePath;

    public function __construct()
    {
        $this->setBasePath('/');
    }

    public function setBasePath($basePath)
    {
        $this->basePath = Formatting::addLeadingSlash(Formatting::addTrailingSlash($basePath));

        // Force the router to rebuild next time we need it
        $this->altoRoutesCreated = false;
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
        return ltrim(preg_replace('/{\s*([a-zA-Z0-9]+)\s*}/s', '[:$1]', $uri), ' /');
    }

    public function map(array $verbs, string $uri, $callback): Route
    {
        // Force all verbs to be uppercase
        $verbs = array_map('strtoupper', $verbs);

        $route = new Route($verbs, $uri, $callback);

        $this->addRoute($route);

        return $route;
    }

    private function createAltoRoutes()
    {
        if ($this->altoRoutesCreated) {
            return;
        }

        $this->altoRouter = new AltoRouter();
        $this->altoRouter->setBasePath($this->basePath);
        $this->altoRoutesCreated = true;

        foreach ($this->routes as $route) {
            $uri = $this->convertUriForAltoRouter($route->getUri());

            // Canonical URI with trailing slash - becomes named route if name is provided
            $this->altoRouter->map(
                implode('|', $route->getMethods()),
                Formatting::addTrailingSlash($uri),
                $route->getAction(),
                $route->getName() ?? null
            );

            // Also register URI without trailing slash
            $this->altoRouter->map(
                implode('|', $route->getMethods()),
                Formatting::removeTrailingSlash($uri),
                $route->getAction()
            );
        }
    }

    public function match(Request $request)
    {
        $this->createAltoRoutes();

        $altoRoute = $this->altoRouter->match($request->getRequestUri(), $request->getMethod());

        // Return a 404 if we don't find anything
        if (!is_callable($altoRoute['target'])) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        // Call the target with any resolved params
        $params = new RouteParams($altoRoute['params']);
        $returnValue = call_user_func($altoRoute['target'], $params);

        // Ensure that we return an instance of a Response object
        if (!($returnValue instanceof Response)) {
            $returnValue = new Response(
                $returnValue,
                Response::HTTP_OK,
                ['content-type' => 'text/html']
            );
        }

        return $returnValue;
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

    public function group($prefix, $callback) : Router
    {
        $group = new RouteGroup($prefix, $this);

        call_user_func($callback, $group);

        return $this;
    }
}
