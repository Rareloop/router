<?php

namespace Rareloop\Router;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Router\Exceptions\NamedRouteNotFoundException;
use Rareloop\Router\Exceptions\RouteParamFailedConstraintException;
use Rareloop\Router\Exceptions\TooLateToAddNewRouteException;
use Rareloop\Router\Helpers\Formatting;
use Rareloop\Router\Invoker;
use Rareloop\Router\MiddlewareResolver;
use Rareloop\Router\Routable;
use Rareloop\Router\Route;
use Rareloop\Router\RouteGroup;
use Rareloop\Router\RouteParams;
use Rareloop\Router\VerbShortcutsTrait;
use Spatie\Macroable\Macroable;
use Zend\Diactoros\Response\TextResponse;
use \AltoRouter;
use mindplay\middleman\Dispatcher;

class Router implements Routable
{
    use VerbShortcutsTrait, Macroable;

    private $routes = [];
    private $altoRouter;
    private $altoRoutesCreated = false;
    private $altoRouterMatchTypeId = 1;
    private $basePath;
    private $currentRoute;

    private $container = null;
    private $middlewareResolver = null;
    private $invoker = null;
    private $baseMiddleware = [];

    public function __construct(ContainerInterface $container = null, MiddlewareResolver $resolver = null)
    {
        if (isset($container)) {
            $this->setContainer($container);
        }

        if (isset($resolver)) {
            $this->middlewareResolver = $resolver;
        }

        $this->setBasePath('/');
    }

    private function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        // Create an invoker for this container. This allows us to use the `call()` method even if
        // the container doesn't support it natively
        $this->invoker = new Invoker($this->container);
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

    private function convertRouteToAltoRouterUri(Route $route, AltoRouter $altoRouter): string
    {
        $output = $route->getUri();

        preg_match_all('/{\s*([a-zA-Z0-9]+\??)\s*}/s', $route->getUri(), $matches);

        $paramConstraints = $route->getParamConstraints();

        for ($i = 0; $i < count($matches[0]); $i++) {
            $match = $matches[0][$i];
            $paramKey = $matches[1][$i];

            $optional = substr($paramKey, -1) === '?';
            $paramKey = trim($paramKey, '?');

            $regex = $paramConstraints[$paramKey] ?? null;
            $matchTypeId = '';

            if (!empty($regex)) {
                $matchTypeId = 'rare' . $this->altoRouterMatchTypeId++;
                $altoRouter->addMatchTypes([
                    $matchTypeId => $regex,
                ]);
            }

            $replacement = '[' . $matchTypeId . ':' . $paramKey . ']';

            if ($optional) {
                $replacement .= '?';
            }

            $output = str_replace($match, $replacement, $output);
        }

        return ltrim($output, ' /');
    }

    public function map(array $verbs, string $uri, $callback): Route
    {
        // Force all verbs to be uppercase
        $verbs = array_map('strtoupper', $verbs);

        $route = new Route($verbs, $uri, $callback, $this->invoker, $this->middlewareResolver);

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
            $uri = $this->convertRouteToAltoRouterUri($route, $this->altoRouter);

            // Canonical URI with trailing slash - becomes named route if name is provided
            $this->altoRouter->map(
                implode('|', $route->getMethods()),
                Formatting::addTrailingSlash($uri),
                $route,
                $route->getName() ?? null
            );

            // Also register URI without trailing slash
            $this->altoRouter->map(
                implode('|', $route->getMethods()),
                Formatting::removeTrailingSlash($uri),
                $route
            );
        }
    }

    public function match(ServerRequestInterface $request)
    {
        $this->createAltoRoutes();

        $altoRoute = $this->altoRouter->match($request->getUri()->getPath(), $request->getMethod());

        $route = $altoRoute['target'] ?? null;
        $params = new RouteParams($altoRoute['params'] ?? []);

        if (!$route) {
            return new TextResponse('Resource not found', 404);
        }

        $this->currentRoute = $route;

        return $this->handle($route, $request, $params);
    }

    protected function handle($route, $request, $params)
    {
        if (count($this->baseMiddleware) === 0) {
            return $route->handle($request, $params);
        }

        // Apply all the base middleware and trigger the route handler as the last in the chain
        $middlewares = array_merge($this->baseMiddleware, [
            function ($request) use ($route, $params) {
                return $route->handle($request, $params);
            },
        ]);

        // Create and process the dispatcher
        $dispatcher = new Dispatcher($middlewares, function ($name) {
            if (!isset($this->middlewareResolver)) {
                return $name;
            }

            return $this->middlewareResolver->resolve($name);
        });

        return $dispatcher->dispatch($request);
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

        // Find the correct route by name so that we can check if the passed in parameters match any
        // constraints that might have been applied
        $matchedRoute = null;

        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                $matchedRoute = $route;
            }
        }

        if ($matchedRoute) {
            $paramConstraints = $matchedRoute->getParamConstraints();

            foreach ($params as $key => $value) {
                $regex = $paramConstraints[$key] ?? false;

                if ($regex) {
                    if (!preg_match('/' . $regex . '/', $value)) {
                        throw new RouteParamFailedConstraintException(
                            'Value `' . $value . '` for param `' . $key . '` fails constraint `' . $regex . '`'
                        );
                    }
                }
            }
        }

        try {
            return $this->altoRouter->generate($name, $params);
        } catch (\Exception $e) {
            throw new NamedRouteNotFoundException($name, null);
        }
    }

    public function group($params, $callback) : Router
    {
        $group = new RouteGroup($params, $this);

        call_user_func($callback, $group);

        return $this;
    }

    public function setBaseMiddleware(array $middleware)
    {
        $this->baseMiddleware = $middleware;
    }

    public function currentRoute()
    {
        return $this->currentRoute;
    }

    public function currentRouteName()
    {
        return $this->currentRoute ? $this->currentRoute->getName() : null;
    }

    public function getRoutes()
    {
        return $this->routes;
    }
}
