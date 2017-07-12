<?php

namespace Rareloop\Router;

use Rareloop\Router\Exceptions\RouteClassStringControllerNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringMethodNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringParseException;
use Rareloop\Router\Exceptions\RouteNameRedefinedException;

class Route
{
    private $uri;
    private $methods = [];
    private $action;
    private $name;

    public function __construct(array $methods, string $uri, $action)
    {
        $this->methods = $methods;
        $this->uri = $uri;
        $this->setAction($action);
    }

    private function setAction($action)
    {
        // Check if this looks like it could be a class/method string
        if (!is_callable($action) && is_string($action)) {
            $action = $this->convertClassStringToClosure($action);
        }

        $this->action = $action;
    }

    private static function convertClassStringToClosure($string)
    {
        @list($className, $method) = explode('@', $string);

        if (!isset($className) || !isset($method)) {
            throw new RouteClassStringParseException('Could not parse route controller from string: `' . $string . '`');
        }

        if (!class_exists($className)) {
            throw new RouteClassStringControllerNotFoundException('Could not find route controller class: `' . $className . '`');
        }

        if (!method_exists($className, $method)) {
            throw new RouteClassStringMethodNotFoundException('Route controller class: `' . $className . '` does not have a `' . $method . '` method');
        }

        return function ($params = null) use ($className, $method) {
            $controller = new $className;
            return $controller->$method($params);
        };
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function name(string $name)
    {
        if (isset($this->name)) {
            throw new RouteNameRedefinedException();
        }

        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }
}
