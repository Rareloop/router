<?php

namespace Rareloop\Router\Test;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Rareloop\Router\Route;
use Rareloop\Router\Router;
use Rareloop\Router\Test\Controllers\TestController;
use Rareloop\Router\Test\Requests\TestRequest;
use Rareloop\Router\Test\Services\TestService;
use Zend\Diactoros\ServerRequest;

class RouterDITest extends TestCase
{
    /** @test */
    public function can_pass_a_container_into_constructor()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);

        $this->assertInstanceOf(Router::class, $router);
    }

    /** @test */
    public function container_passed_to_constructor_must_be_psr_11_compatible()
    {
        $this->expectException(\TypeError::class);

        $container = new \stdClass;
        $router = new Router($container);

        $this->assertInstanceOf(Router::class, $router);
    }

    /** @test */
    public function route_params_are_injected_into_closure()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);
        $count = 0;

        $router->get('/posts/{postId}/comments/{commentId}', function (int $postId, int $commentId) use (&$count) {
            $count++;

            $this->assertSame(1, $postId);
            $this->assertSame(2, $commentId);

            return 'abc';
        });

        $request = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function typehints_are_injected_into_closure()
    {
        $container = ContainerBuilder::buildDevContainer();
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);

        $router = new Router($container);
        $count = 0;

        $router->get('/test/route', function (TestService $test) use (&$count, $testServiceInstance) {
            $count++;

            $this->assertSame($testServiceInstance, $test);
            $this->assertSame('abc123', $test->value);

            return 'abc';
        });

        $request = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function typehints_are_injected_into_closure_with_params()
    {
        $container = ContainerBuilder::buildDevContainer();
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);

        $router = new Router($container);
        $count = 0;

        $router->get('/posts/{postId}/comments/{commentId}', function (TestService $test, int $postId, int $commentId) use (&$count, $testServiceInstance) {
            $count++;

            $this->assertSame($testServiceInstance, $test);
            $this->assertSame('abc123', $test->value);
            $this->assertSame(1, $postId);
            $this->assertSame(2, $commentId);

            return 'abc';
        });

        $request = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function route_params_are_injected_into_closure_regardless_of_param_order()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);
        $count = 0;

        $router->get('/posts/{postId}/comments/{commentId}', function (int $commentId, int $postId) use (&$count) {
            $count++;

            $this->assertSame(1, $postId);
            $this->assertSame(2, $commentId);

            return 'abc';
        });

        $request = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function reflection_error_is_thrown_when_typehints_cant_be_resolved_from_the_container()
    {
        $this->expectException(\ReflectionException::class);

        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);

        $router->get('/test/route', function (UndefinedType $test) {});

        $request = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);
    }

    /** @test */
    public function route_params_are_injected_into_controller_class()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);

        $router->get('/posts/{postId}/comments/{commentId}', 'Rareloop\Router\Test\Controllers\TestController@expectsInjectedParams');

        $request = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('$postId: 1 $commentId: 2', $response->getBody()->getContents());
    }

    /** @test */
    public function typehints_are_injected_into_controller_class()
    {
        $container = ContainerBuilder::buildDevContainer();
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);

        $router = new Router($container);

        $router->get('/test/route', 'Rareloop\Router\Test\Controllers\TestController@typeHintTestService');

        $request = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function typehints_are_injected_into_controller_class_with_params()
    {
        $container = ContainerBuilder::buildDevContainer();
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);

        $router = new Router($container);

        $router->get('/posts/{postId}/comments/{commentId}', 'Rareloop\Router\Test\Controllers\TestController@typeHintTestServiceWithParams');

        $request = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('$postId: 1 $commentId: 2 TestService: abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_inject_request_object()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request = new ServerRequest([], [], '/test/route', 'GET');
        $router = new Router($container);
        $count = 0;

        $router->get('/test/route', function (ServerRequest $injectedRequest) use ($request, &$count) {
            $count++;

            $this->assertInstanceOf(ServerRequest::class, $injectedRequest);
            $this->assertSame('GET', $injectedRequest->getMethod());
            $this->assertSame('/test/route', $injectedRequest->getUri()->getPath());

            return 'abc123';
        });


        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_inject_request_object_with_a_body()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request = new ServerRequest([], [], '/test/route', 'POST', 'php://input', [], [], [], 'post body');
        $router = new Router($container);
        $count = 0;

        $router->post('/test/route', function (ServerRequest $injectedRequest) use ($request, &$count) {
            $count++;

            $this->assertInstanceOf(ServerRequest::class, $injectedRequest);
            $this->assertSame('POST', $injectedRequest->getMethod());
            $this->assertSame('/test/route', $injectedRequest->getUri()->getPath());
            $this->assertSame('post body', $injectedRequest->getParsedBody());

            return 'abc123';
        });


        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_inject_request_sub_class()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request = new ServerRequest([], [], '/test/route', 'GET');
        $router = new Router($container);

        $count = 0;

        $router->get('/test/route', function (TestRequest $injectedRequest) use ($request, &$count) {
            $count++;

            $this->assertInstanceOf(TestRequest::class, $injectedRequest);
            $this->assertSame('GET', $injectedRequest->getMethod());
            $this->assertSame('/test/route', $injectedRequest->getUri()->getPath());

            return 'abc123';
        });


        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function constructor_params_are_injected_into_controller_class()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);

        $router->get('/test/url', 'Rareloop\Router\Test\Controllers\TestConstructorParamController@returnTestServiceValue');

        $request = new ServerRequest([], [], '/test/url', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }
}
