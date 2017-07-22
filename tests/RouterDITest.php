<?php

namespace Rareloop\Router\Test;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Rareloop\Router\Route;
use Rareloop\Router\Router;
use Rareloop\Router\Test\Controllers\TestController;
use Rareloop\Router\Test\Requests\TestRequest;
use Rareloop\Router\Test\Services\TestService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $request = Request::create('/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getContent());
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

        $request = Request::create('/test/route', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getContent());
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

        $request = Request::create('/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getContent());
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

        $request = Request::create('/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getContent());
    }

    /** @test */
    public function reflection_error_is_thrown_when_typehints_cant_be_resolved_from_the_container()
    {
        $this->expectException(\ReflectionException::class);

        $container = ContainerBuilder::buildDevContainer();
        $router = new Router($container);

        $router->get('/test/route', function (UndefinedType $test) {});

        $request = Request::create('/test/route', 'GET');
        $response = $router->match($request);
    }

    /** @test */
    public function route_params_are_injected_into_controller_class()
    {
        $container = ContainerBuilder::buildDevContainer();
        $container->set('TestController', \DI\object(TestController::class));
        $router = new Router($container);

        $router->get('/posts/{postId}/comments/{commentId}', 'TestController@expectsInjectedParams');

        $request = Request::create('/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('$postId: 1 $commentId: 2', $response->getContent());
    }

    /** @test */
    public function typehints_are_injected_into_controller_class()
    {
        $container = ContainerBuilder::buildDevContainer();
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);
        $container->set('TestController', \DI\object(TestController::class));

        $router = new Router($container);

        $router->get('/test/route', 'TestController@typeHintTestService');

        $request = Request::create('/test/route', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function typehints_are_injected_into_controller_class_with_params()
    {
        $container = ContainerBuilder::buildDevContainer();
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);
        $container->set('TestController', \DI\object(TestController::class));

        $router = new Router($container);

        $router->get('/posts/{postId}/comments/{commentId}', 'TestController@typeHintTestServiceWithParams');

        $request = Request::create('/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('$postId: 1 $commentId: 2 TestService: abc123', $response->getContent());
    }

    /** @test */
    public function can_inject_request_object()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request = Request::create('/test/route', 'GET');
        $router = new Router($container);

        $router->get('/test/route', function (Request $injectedRequest) use ($request) {
            $this->assertSame($request, $injectedRequest);

            return 'abc123';
        });


        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getContent());
    }

    /** @test */
    public function can_inject_request_sub_class()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request = Request::create('/test/route', 'GET');
        $router = new Router($container);

        $count = 0;

        $router->get('/test/route', function (TestRequest $injectedRequest) use ($request, &$count) {
            $count++;

            $this->assertInstanceOf(TestRequest::class, $injectedRequest);
            $this->assertSame('GET', $injectedRequest->getMethod());
            $this->assertSame('/test/route', $injectedRequest->getRequestUri());

            return 'abc123';
        });


        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getContent());
    }
}
