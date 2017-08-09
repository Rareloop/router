<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Rareloop\Router\ResponseFactory;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\TextResponse;

class ResponseFactoryTest extends TestCase
{
    /** @test */
    public function when_passed_a_response_instance_the_same_object_is_returned()
    {
        $response = new TextResponse('Testing', 200);

        $this->assertSame($response, ResponseFactory::create($response));
    }

    /** @test */
    public function when_passed_a_non_response_instance_a_response_object_is_returned()
    {
        $response = ResponseFactory::create('Testing');

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Testing', $response->getBody()->getContents());
    }

    /** @test */
    public function when_nothing_is_passed_an_empty_response_object_is_returned()
    {
        $response = ResponseFactory::create();

        $this->assertInstanceOf(EmptyResponse::class, $response);
    }
}
