<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Rareloop\Router\ControllerMiddlewareOptions;

class ControllerMiddlewareOptionsTest extends TestCase
{
    /** @test */
    public function by_default_no_methods_are_excluded()
    {
        $options = new ControllerMiddlewareOptions;

        $this->assertFalse($options->excludedForMethod('foo'));
        $this->assertFalse($options->excludedForMethod('bar'));
    }

    /** @test */
    public function only_is_chainable()
    {
        $options = new ControllerMiddlewareOptions;

        $this->assertSame($options, $options->only('foo'));
    }

    /** @test */
    public function can_use_only_to_limit_methods()
    {
        $options = new ControllerMiddlewareOptions;

        $options->only('foo');

        $this->assertFalse($options->excludedForMethod('foo'));
        $this->assertTrue($options->excludedForMethod('bar'));
    }

    /** @test */
    public function can_use_only_to_limit_multiple_methods()
    {
        $options = new ControllerMiddlewareOptions;

        $options->only(['foo', 'bar']);

        $this->assertFalse($options->excludedForMethod('foo'));
        $this->assertFalse($options->excludedForMethod('bar'));
        $this->assertTrue($options->excludedForMethod('baz'));
    }

    /** @test */
    public function except_is_chainable()
    {
        $options = new ControllerMiddlewareOptions;

        $this->assertSame($options, $options->except('foo'));
    }

    /** @test */
    public function can_use_except_to_limit_methods()
    {
        $options = new ControllerMiddlewareOptions;

        $options->except('foo');

        $this->assertTrue($options->excludedForMethod('foo'));
        $this->assertFalse($options->excludedForMethod('bar'));
    }

    /** @test */
    public function can_use_except_to_limit_multiple_methods()
    {
        $options = new ControllerMiddlewareOptions;

        $options->except(['foo', 'bar']);

        $this->assertTrue($options->excludedForMethod('foo'));
        $this->assertTrue($options->excludedForMethod('bar'));
        $this->assertFalse($options->excludedForMethod('baz'));
    }
}
