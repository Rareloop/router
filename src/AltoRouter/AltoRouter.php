<?php

namespace Rareloop\Router\AltoRouter;

class AltoRouter extends \AltoRouter
{
    /**
     * Give a name to the route with the provided index
     *
     * @param string $name       Route name
     * @param integer $routeIndex Index of the route
     */
    public function setRouteName($name, $routeIndex)
    {
        if (isset($this->namedRoutes[$name])) {
            throw new \Exception("Can not redeclare route '{$name}'");
        } else {
            if ($routeIndex >= count($this->routes)) {
                throw new \Exception('Can not find route at index \'' . $routeIndex . '\'');
            }

            $route = $this->routes[$routeIndex];
            $this->namedRoutes[$name] = $route;
        }
    }
}
