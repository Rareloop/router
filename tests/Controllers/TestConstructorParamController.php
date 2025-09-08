<?php

namespace Rareloop\Router\Test\Controllers;

use Rareloop\Router\Test\Services\TestService;

class TestConstructorParamController
{
    public function __construct(private readonly TestService $testService)
    {
    }

    public function returnTestServiceValue()
    {
        return $this->testService->value;
    }
}
