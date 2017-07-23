<?php

namespace Rareloop\Router;

use Rareloop\Router\Exceptions\RouteClassStringControllerNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringMethodNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringParseException;
use Rareloop\Router\Exceptions\RouteNameRedefinedException;
use Rareloop\Router\Invoker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Route
{
    private $uri;
    private $methods = [];
    private $action;
    private $name;
    private $invoker = null;

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

        return function ($params = null) use ($className, $method) {
            $controller = new $className;
            return $controller->$method($params);
        };
    }

    private function isUsingContainer()
    {
        return isset($this->invoker);
    }

    public function handle(Request $request, RouteParams $params) : Response
    {
        if ($this->isUsingContainer()) {
            $output = $this->invoker->setRequest($request)->call($this->action, $params->toArray());
        } else {
            // Call the target with any resolved params
            $output = call_user_func($this->action, $params);
        }

        return $this->createResponse($output);
    }

    private function createResponse($output) : Response
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
