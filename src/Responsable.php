<?php

namespace Rareloop\Router;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Responsable
{
    public function toResponse(RequestInterface $request) : ResponseInterface;
}
