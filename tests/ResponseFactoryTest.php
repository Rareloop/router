<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rareloop\Router\Responsable;
use Rareloop\Router\ResponseFactory;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;

class ResponseFactoryTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private ServerRequest $request;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = new ServerRequest([], [], '/test/123', 'GET');
    }

    #[Test]
    public function when_passed_a_response_instance_the_same_object_is_returned()
    {
        $response = new TextResponse('Testing', 200);

        $this->assertSame($response, ResponseFactory::create($this->request, $response));
    }

    #[Test]
    public function when_passed_a_non_response_instance_a_response_object_is_returned()
    {
        $response = ResponseFactory::create($this->request, 'Testing');

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Testing', $response->getBody()->getContents());
    }

    #[Test]
    public function when_nothing_is_passed_an_empty_response_object_is_returned()
    {
        $response = ResponseFactory::create($this->request);

        $this->assertInstanceOf(EmptyResponse::class, $response);
    }

    #[Test]
    public function when_a_responsable_object_is_passed_the_response_object_is_returned()
    {
        $textResponse = new TextResponse('testing123');
        $object = \Mockery::mock(ResponsableObject::class);
        $object->shouldReceive('toResponse')->with($this->request)->once()->andReturn($textResponse);

        $response = ResponseFactory::create($this->request, $object);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertSame('testing123', $response->getBody()->getContents());
    }
}

class ResponsableObject implements Responsable
{
    public function toResponse(RequestInterface $request): ResponseInterface
    {
        return new TextResponse('testing123');
    }
}
