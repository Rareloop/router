<?php

namespace Rareloop\Router;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Rareloop\Router\Exceptions\RouteClassStringControllerNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringMethodNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringParseException;
use Rareloop\Router\Exceptions\RouteNameRedefinedException;
use Rareloop\Router\Invoker;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\ServerRequest;
use mindplay\middleman\Dispatcher;

class Route
{
    private $uri;
    private $methods = [];
    private $action;
    private $name;
    private $invoker = null;
    private $middleware = [];
    private $paramConstraints = [];
    private $controllerName = null;
    private $controllerMethod = null;

    public function __construct(array $methods, string $uri, $action, Invoker $invoker = null)
    {
        $this->invoker = $invoker;

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
        // Check if this looks like it could be a class/method string
        if (!is_callable($action) && is_string($action)) {
            $action = $this->convertClassStringToClosure($action);
        }

        $this->action = $action;
    }

    private function convertClassStringToClosure($string)
    {
        $this->controllerName = null;
        $this->controllerMethod = null;

        @list($className, $method) = explode('@', $string);

        if (!isset($className) || !isset($method)) {
            throw new RouteClassStringParseException('Could not parse route controller from string: `' . $string . '`');
        }

        if (isset($this->invoker)) {
            return [$className, $method];
        }

        if (!class_exists($className)) {
            throw new RouteClassStringControllerNotFoundException('Could not find route controller class: `' . $className . '`');
        }

        if (!method_exists($className, $method)) {
            throw new RouteClassStringMethodNotFoundException('Route controller class: `' . $className . '` does not have a `' . $method . '` method');
        }

        $this->controllerName = $className;
        $this->controllerMethod = $method;

        return function ($params = null) use ($className, $method) {
            $controller = new $className;
            return $controller->$method($params);
        };
    }

    private function isUsingContainer()
    {
        return isset($this->invoker);
    }

    public function handle(ServerRequest $request, RouteParams $params) : ResponseInterface
    {
        // Get all the middleware registered for this route
        $middlewares = $this->gatherMiddleware();

        // Add our route handler as the last item
        $middlewares[] = function ($request) use ($params) {
            if ($this->isUsingContainer()) {
                $output = $this->invoker->setRequest($request)->call($this->action, $params->toArray());
            } else {
                // Call the target with any resolved params
                $output = call_user_func($this->action, $params);
            }

            return ResponseFactory::create($output, $request);
        };

        // Create and process the dispatcher
        $dispatcher = new Dispatcher($middlewares);
        return $dispatcher->dispatch($request);
    }

    private function gatherMiddleware(): array
    {
        return array_merge([], $this->middleware);
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
        $callableName = null;

        if (isset($this->controllerName) && isset($this->controllerMethod)) {
            return $this->controllerName . '@' . $this->controllerMethod;
        }

        if (is_callable($this->action, false, $callableName)) {
            list($controller, $method) = explode('::', $callableName);

            if ($controller === 'Closure') {
                return $controller;
            }

            return $controller . '@' . $method;
        }
    }
}
