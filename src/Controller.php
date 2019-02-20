<?php

namespace Rareloop\Router;

use Rareloop\Router\ProvidesControllerMiddlewareTrait;

abstract class Controller implements ProvidesControllerMiddleware
{
    use ProvidesControllerMiddlewareTrait;
}
