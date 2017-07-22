<?php

namespace Rareloop\Router;

use Psr\Container\ContainerInterface;
use Rareloop\Router\Exceptions\NamedRouteNotFoundException;
use Rareloop\Router\Exceptions\TooLateToAddNewRouteException;
use Rareloop\Router\Helpers\Formatting;
use Rareloop\Router\Invoker;
use Rareloop\Router\Routable;
use Rareloop\Router\Route;
use Rareloop\Router\RouteGroup;
use Rareloop\Router\RouteParams;
use Rareloop\Router\VerbShortcutsTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \AltoRouter;

class Router implements Routable
{
    use VerbShortcutsTrait;

    private $routes = [];
    private $altoRouter;
    private $altoRoutesCreated = false;
    private $basePath;

    private $container = null;
    private $invoker = null;

    public function __construct(ContainerInterface $container = null)
    {
        if (isset($container)) {
            $this->setContainer($container);
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

    private function convertUriForAltoRouter(string $uri): string
    {
        return ltrim(preg_replace('/{\s*([a-zA-Z0-9]+)\s*}/s', '[:$1]', $uri), ' /');
    }

    public function map(array $verbs, string $uri, $callback): Route
    {
        // Force all verbs to be uppercase
        $verbs = array_map('strtoupper', $verbs);

        $route = new Route($verbs, $uri, $callback, $this->container);

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

        $target = $altoRoute['target'];
        $params = new RouteParams($altoRoute['params'] ?? []);

        // Return a 404 if the target isn't invokable
        if (!$this->isTargetInvokable($target)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $output = $this->invokeRouteAction($target, $request, $params);

        return $this->createResponse($output);
    }

    private function createResponse($output)
    {
        if ($output instanceof Response) {
            return $output;
        }

        return new Response(
            $output,
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }

    private function isTargetInvokable($target)
    {
        if ($this->hasContainer()) {
            return is_callable($target) || (is_array($target) && count($target) === 2);
        }

        return is_callable($target);
    }

    private function hasContainer()
    {
        return isset($this->invoker);
    }

    private function invokeRouteAction($target, Request $request, RouteParams $params)
    {
        if ($this->hasContainer()) {
            return $this->invoker->setRequest($request)->call($target, $params->toArray());
        } else {
            // Call the target with any resolved params
            return call_user_func($target, $params);
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

    public function group($prefix, $callback) : Router
    {
        $group = new RouteGroup($prefix, $this);

        call_user_func($callback, $group);

        return $this;
    }
}
