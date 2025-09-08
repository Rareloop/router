<?php

namespace Rareloop\Router;

class RouteParams implements \Iterator
{
    private $position = 0;

    public function __construct(private array $params)
    {
    }

    public function __get($key)
    {
        if (!isset($this->params[$key])) {
            return null;
        }

        return $this->params[$key];
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->params[$this->key()];
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        $keys = array_keys($this->params);
        return $keys[$this->position];
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->position++;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->position < count($this->params);
    }

    public function toArray()
    {
        return $this->params;
    }
}
