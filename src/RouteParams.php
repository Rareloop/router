<?php

namespace Rareloop\Router;

class RouteParams implements \Iterator
{
    private $position = 0;
    private $params = [];

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function __get($key)
    {
        if (!isset($this->params[$key])) {
            trigger_error('undefined property ' . $key);
        }

        return $this->params[$key];
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->params[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->position++;
    }

    public function valid()
    {
        return isset($this->params[$this->position]);
    }
}
