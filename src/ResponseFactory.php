<?php

/**
 * @phpcs:disable PEAR.Functions.ValidDefaultValue
 */

namespace Rareloop\Router;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rareloop\Router\Responsable;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;

class ResponseFactory
{
    public static function create(RequestInterface $request, $response = '')
    {
        if (empty($response)) {
            return new EmptyResponse();
        }

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        if ($response instanceof Responsable) {
            return $response->toResponse($request);
        }

        return new HtmlResponse($response);
    }
}
