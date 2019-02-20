<?php

namespace Rareloop\Router;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Router\ControllerMiddleware;
use Rareloop\Router\Exceptions\RouteClassStringControllerNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringMethodNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringParseException;
use Rareloop\Router\Invoker;
use Rareloop\Router\ProvidesControllerMiddleware;

class RouteAction
{
    protected $callable;
    protected $controller;
    protected $invoker;

    protected $controllerName;
    protected $controllerMethod;

    /**
     * Constructor
     *
     * Actions created with a Controller string (e.g. `MyController@myMethod`) are lazy loaded
     * and the Controller class will only be instantiated when required
     *
     * @param mixed $action
     * @param Rareloop/Router/Invoker $invoker
     */
    public function __construct($action, Invoker $invoker = null)
    {
        $this->invoker = $invoker;
        $this->callable = $this->createCallableFromAction($action);
    }

    /**
     * Invoke the action
     *
     * @param  ServerRequestInterface $request
     * @param  RouteParams            $params
     * @return mixed
     */
    public function invoke(ServerRequestInterface $request, RouteParams $params)
    {
        $callable = $this->callable;

        // Controller Actions are lazy loaded so we need to call the factory to get the callable
        if ($this->isControllerAction()) {
            $callable = call_user_func($this->callable);
        }

        // Call the target action with any provided params
        if ($this->invoker) {
            return $this->invoker->setRequest($request)->call($callable, $params->toArray());
        } else {
            return call_user_func($callable, $params);
        }
    }

    /**
     * If the action is a Controller string, a factory callable is returned to allow for lazy loading
     *
     * @param  mixed $action
     * @return callable
     */
    private function createCallableFromAction($action) : callable
    {
        // Check if this looks like it could be a class/method string
        if (!is_callable($action) && is_string($action)) {
            return $this->convertClassStringToFactory($action);
        }

        return $action;
    }

    /**
     * Is this a known Controller based action?
     *
     * @return boolean
     */
    private function isControllerAction() : bool
    {
        return !empty($this->controllerName) && !empty($this->controllerMethod);
    }

    /**
     * Get the Controller for this action. The Controller will only be created once
     *
     * @return mixed Returns null if this is not a Controller based action
     */
    private function getController()
    {
        if (empty($this->controllerName)) {
            return null;
        }

        if (isset($this->controller)) {
            return $this->controller;
        }

        $this->controller = $this->createControllerFromClassName($this->controllerName);

        return $this->controller;
    }

    /**
     * Instantiate a Controller object from the provided class name
     *
     * @param  string $className
     * @return mixed
     */
    private function createControllerFromClassName($className)
    {
        // If we can, use the container to build the Controller so that Constructor params can
        // be injected where possible
        if ($this->invoker) {
            return $this->invoker->getContainer()->get($className);
        }

        return new $className;
    }

    /**
     * Can this action provide Middleware
     *
     * @return bool
     */
    private function providesMiddleware() : bool
    {
        $controller = $this->getController();

        if ($controller && ($controller instanceof ProvidesControllerMiddleware)) {
            return true;
        }

        return false;
    }

    /**
     * Get an array of Middleware
     *
     * @return array
     */
    public function getMiddleware() : array
    {
        if (!$this->providesMiddleware()) {
            return [];
        }

        $allControllerMiddleware = array_filter(
            $this->getController()->getControllerMiddleware(),
            function (ControllerMiddleware $middleware) {
                return !$middleware->excludedForMethod($this->controllerMethod);
            }
        );

        return array_map(
            function ($controllerMiddleware) {
                return $controllerMiddleware->middleware();
            },
            $allControllerMiddleware
        );
    }

    /**
     * Create a factory Closure for the given Controller string
     *
     * @param  string $string e.g. `MyController@myMethod`
     * @return Closure
     */
    private function convertClassStringToFactory($string) : Closure
    {
        $this->controllerName = null;
        $this->controllerMethod = null;

        @list($className, $method) = explode('@', $string);

        if (!isset($className) || !isset($method)) {
            throw new RouteClassStringParseException('Could not parse route controller from string: `' . $string . '`');
        }

        if (!class_exists($className)) {
            throw new RouteClassStringControllerNotFoundException(
                'Could not find route controller class: `' . $className . '`'
            );
        }

        if (!method_exists($className, $method)) {
            throw new RouteClassStringMethodNotFoundException(
                'Route controller class: `' . $className . '` does not have a `' . $method . '` method'
            );
        }

        $this->controllerName = $className;
        $this->controllerMethod = $method;

        return function () {
            $controller = $this->getController();
            $method = $this->controllerMethod;

            if ($this->invoker) {
                return [$controller, $method];
            }

            return function ($params = null) use ($controller, $method) {
                return $controller->$method($params);
            };
        };
    }

    /**
     * Get the human readable name of this action
     *
     * @return string
     */
    public function getActionName()
    {
        $callableName = null;

        if ($this->isControllerAction()) {
            return $this->controllerName . '@' . $this->controllerMethod;
        }

        if (is_callable($this->callable, false, $callableName)) {
            list($controller, $method) = explode('::', $callableName);

            if ($controller === 'Closure') {
                return $controller;
            }

            return $controller . '@' . $method;
        }
    }
}
