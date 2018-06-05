<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rareloop\Router\Responsable;
use Rareloop\Router\ResponseFactory;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequest;

class ResponseFactoryTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function setUp()
    {
        parent::setUp();

        $this->request = new ServerRequest([], [], '/test/123', 'GET');
    }

    /** @test */
    public function when_passed_a_response_instance_the_same_object_is_returned()
    {
        $response = new TextResponse('Testing', 200);

        $this->assertSame($response, ResponseFactory::create($response, $this->request));
    }

    /** @test */
    public function when_passed_a_non_response_instance_a_response_object_is_returned()
    {
        $response = ResponseFactory::create('Testing', $this->request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Testing', $response->getBody()->getContents());
    }

    /** @test */
    public function when_nothing_is_passed_an_empty_response_object_is_returned()
    {
        $response = ResponseFactory::create(null, $this->request);

        $this->assertInstanceOf(EmptyResponse::class, $response);
    }

    /** @test */
    public function when_a_responsable_object_is_passed_the_response_object_is_returned()
    {
        $textResponse = new TextResponse('testing123');
        $object = \Mockery::mock(ResponsableObject::class);
        $object->shouldReceive('toResponse')->with($this->request)->once()->andReturn($textResponse);

        $response = ResponseFactory::create($object, $this->request);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertSame('testing123', $response->getBody()->getContents());
    }
}

class ResponsableObject implements Responsable
{
    public function toResponse(RequestInterface $request) : ResponseInterface
    {
        return new TextResponse('testing123');
    }
}
