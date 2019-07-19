<?php

namespace Rareloop\Router;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Rareloop\Router\Exceptions\RouteClassStringControllerNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringMethodNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringParseException;
use Rareloop\Router\Exceptions\RouteNameRedefinedException;
use Rareloop\Router\Invoker;
use Rareloop\Router\ProvidesMiddleware;
use Spatie\Macroable\Macroable;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\ServerRequest;
use mindplay\middleman\Dispatcher;

class Route
{
    use Macroable;

    private $uri;
    private $methods = [];
    private $routeAction;
    private $name;
    private $invoker = null;
    private $middlewareResolver = null;
    private $middleware = [];
    private $paramConstraints = [];
    private $controllerName = null;
    private $controllerMethod = null;

    public function __construct(
        array $methods,
        string $uri,
        $action,
        Invoker $invoker = null,
        MiddlewareResolver $resolver = null
    ) {
        $this->invoker = $invoker;
        $this->middlewareResolver = $resolver;

        $this->methods = $methods;
        $this->setUri($uri);
        $this->setAction($action);
    }

    private function setUri($uri)
    {
        $this->uri = rtrim($uri, ' /');
    }

    private function setAction($action)
    {
        $this->routeAction = new RouteAction($action, $this->invoker);
    }

    public function handle(ServerRequest $request, RouteParams $params) : ResponseInterface
    {
        // Get all the middleware registered for this route
        $middlewares = $this->gatherMiddleware();

        // Add our route handler as the last item
        $middlewares[] = function ($request) use ($params) {
            $output = $this->routeAction->invoke($request, $params);

            return ResponseFactory::create($output, $request);
        };

        // Create and process the dispatcher
        $dispatcher = new Dispatcher($middlewares, function ($name) {
            if (!isset($this->middlewareResolver)) {
                return $name;
            }

            return $this->middlewareResolver->resolve($name);
        });

        return $dispatcher->dispatch($request);
    }

    private function gatherMiddleware() : array
    {
        return array_merge([], $this->middleware, $this->routeAction->getMiddleware());
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function name(string $name)
    {
        if (isset($this->name)) {
            throw new RouteNameRedefinedException();
        }

        $this->name = $name;

        return $this;
    }

    public function where()
    {
        $args = func_get_args();

        if (count($args) === 0) {
            throw new InvalidArgumentException();
        }

        if (is_array($args[0])) {
            foreach ($args[0] as $key => $value) {
                $this->paramConstraints[$key] = $value;
            }
        } else {
            $this->paramConstraints[$args[0]] = $args[1];
        }

        return $this;
    }

    public function getParamConstraints()
    {
        return $this->paramConstraints;
    }

    public function middleware()
    {
        $args = func_get_args();

        foreach ($args as $middleware) {
            if (is_array($middleware)) {
                $this->middleware += $middleware;
            } else {
                $this->middleware[] = $middleware;
            }
        }

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getActionName()
    {
        return $this->routeAction->getActionName();
    }
}
