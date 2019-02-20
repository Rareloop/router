<?php

namespace Rareloop\Router;

class ControllerMiddlewareOptions
{
    /**
     * @var array
     */
    protected $only = [];

    /**
     * @var array
     */
    protected $except = [];

    /**
     * Specify the methods that the middleware applies to
     *
     * @param  string|array $method
     * @return Rareloop\Router\ControllerMiddlewareOptions
     */
    public function only($method)
    {
        if (!is_array($method)) {
            $method = [ $method ];
        }

        $this->only += $method;

        return $this;
    }

    /**
     * Specify the methods that the middleware does not apply to
     *
     * @param  string|array $method
     * @return Rareloop\Router\ControllerMiddlewareOptions
     */
    public function except($method)
    {
        if (!is_array($method)) {
            $method = [ $method ];
        }

        $this->except += $method;

        return $this;
    }

    /**
     * Is a specific method excluded by the options set on this object
     *
     * @param  string $method
     * @return bool
     */
    public function excludedForMethod($method) : bool
    {
        if (empty($this->only) && empty($this->except)) {
            return false;
        }

        return (!empty($this->only) && !in_array($method, $this->only)) ||
            (!empty($this->except) && in_array($method, $this->except));
    }
}
