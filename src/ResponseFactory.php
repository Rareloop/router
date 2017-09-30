<?php

namespace Rareloop\Router;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\HtmlResponse;

class ResponseFactory
{
    public static function create($response = '')
    {
        if (empty($response)) {
            return new EmptyResponse();
        }

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        return new HtmlResponse($response);
    }
}
