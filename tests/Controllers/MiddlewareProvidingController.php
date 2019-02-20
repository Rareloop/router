<?php

namespace Rareloop\Router\Test\Controllers;

use Rareloop\Router\Controller;
use Rareloop\Router\ControllerMiddlewareOptions;
use Rareloop\Router\ProvidesControllerMiddleware;
use Rareloop\Router\Test\Middleware\AddHeaderMiddleware;

class MiddlewareProvidingController extends Controller
{
    public function returnOne()
    {
        return 'One';
    }

    public function returnTwo()
    {
        return 'Two';
    }

    public function returnThree()
    {
        return 'Three';
    }
}
