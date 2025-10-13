<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Rareloop\Router\Helpers\Formatting;

class FormattingTest extends TestCase
{
    /** @test */
    public function can_remove_trialing_slash()
    {
        $string = 'string/';

        $this->assertSame('string', Formatting::removeTrailingSlash($string));
    }

    /** @test */
    public function can_add_trialing_slash()
    {
        $string = 'string';

        $this->assertSame('string/', Formatting::addTrailingSlash($string));
    }

    /** @test */
    public function add_trialing_slash_does_not_produce_duplicates()
    {
        $string = 'string/';

        $this->assertSame('string/', Formatting::addTrailingSlash($string));
    }

    /** @test */
    public function can_remove_leading_slash()
    {
        $string = '/string';

        $this->assertSame('string', Formatting::removeLeadingSlash($string));
    }

    /** @test */
    public function can_add_leading_slash()
    {
        $string = 'string';

        $this->assertSame('/string', Formatting::addLeadingSlash($string));
    }

    /** @test */
    public function add_leading_slash_does_not_produce_duplicates()
    {
        $string = '/string';

        $this->assertSame('/string', Formatting::addLeadingSlash($string));
    }
}
