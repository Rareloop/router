<?php

namespace Rareloop\Router\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Router\Exceptions\NamedRouteNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringControllerNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringMethodNotFoundException;
use Rareloop\Router\Exceptions\RouteClassStringParseException;
use Rareloop\Router\Exceptions\RouteParamFailedConstraintException;
use Rareloop\Router\Exceptions\TooLateToAddNewRouteException;
use Rareloop\Router\Route;
use Rareloop\Router\RouteGroup;
use Rareloop\Router\RouteParams;
use Rareloop\Router\Router;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequest;

class RouterTest extends TestCase
{
    /** @test */
    public function map_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->map(['GET'], '/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function map_accepts_lowercase_verbs()
    {
        $router = new Router;

        $route = $router->map(['get', 'post', 'put', 'patch', 'delete', 'options'], '/test/123', function () {});

        $this->assertSame(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route->getMethods());
    }

    /** @test */
    public function get_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->get('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function post_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->post('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['POST'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function patch_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->patch('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['PATCH'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function put_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->put('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['PUT'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function delete_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->delete('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['DELETE'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function options_returns_a_route_object()
    {
        $router = new Router;

        $route = $router->options('/test/123', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['OPTIONS'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function map_removes_trailing_slash_from_uri()
    {
        $router = new Router;

        $route = $router->map(['GET'], '/test/123/', function () {});

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function no_return_from_route_action_results_in_a_204_status_code()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('test/123', function () use (&$count) {
            $count++;
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(204, $response->getStatusCode());
    }

    /** @test */
    public function leading_slash_is_optional_when_creating_a_route()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('test/123', function () use (&$count) {
            $count++;
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function match_returns_a_response_object()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function match_does_not_mutate_returned_response_object()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $response = new TextResponse('This is a test', 202, ['content-type' => 'text/plain']);

        $route = $router->get('/test/123', function () use (&$response) {
            return $response;
        });
        $routerResponse = $router->match($request);

        $this->assertSame($response, $routerResponse);
    }

    /** @test */
    public function match_returns_a_404_response_object_when_route_is_not_found()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;

        $response = $router->match($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function match_works_with_a_closure()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function match_uri_with_trailing_when_route_has_been_defined_without_trailing_slash()
    {
        $request = new ServerRequest([], [], '/test/123/', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function match_uri_with_trailing_when_route_has_been_defined_with_trailing_slash()
    {
        $request = new ServerRequest([], [], '/test/123/', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123/', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function match_uri_without_trailing_when_route_has_been_defined_without_trailing_slash()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function match_uri_without_trailing_when_route_has_been_defined_with_trailing_slash()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123/', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function match_works_with_a_class_and_method_string()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;

        $route = $router->get('/test/123', 'Rareloop\Router\Test\Controllers\TestController@returnHelloWorld');
        $response = $router->match($request);

        $this->assertSame('Hello World', $response->getBody()->getContents());
    }

    /** @test */
    public function match_throws_exception_with_invalid_class_and_string_method()
    {
        $this->expectException(RouteClassStringParseException::class);

        $router = new Router;

        $route = $router->get('/test/123', 'Rareloop\Router\Test\Controllers\TestController:returnHelloWorld');
    }

    /** @test */
    public function match_throws_exception_when_class_and_string_method_contains_an_unfound_class()
    {
        $this->expectException(RouteClassStringControllerNotFoundException::class);

        $router = new Router;

        $route = $router->get('/test/123', 'Rareloop\Router\Test\Controllers\UndefinedController@returnHelloWorld');
    }

    /** @test */
    public function match_throws_exception_when_class_and_string_method_contains_an_unfound_method()
    {
        $this->expectException(RouteClassStringMethodNotFoundException::class);

        $router = new Router;

        $route = $router->get('/test/123', 'Rareloop\Router\Test\Controllers\TestController@undefinedMethod');
    }

    /** @test */
    public function params_are_parsed_and_passed_into_callback_function()
    {
        $request = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $router = new Router;

        $route = $router->get('/posts/{postId}/comments/{commentId}', function ($params) use (&$count) {
            $count++;

            $this->assertInstanceOf(RouteParams::class, $params);
            $this->assertSame('123', $params->postId);
            $this->assertSame('abc', $params->commentId);
        });
        $router->match($request);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function params_are_parsed_and_passed_into_callback_function_when_surrounded_by_whitespace()
    {
        $request = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $router = new Router;

        $route = $router->get('/posts/{ postId }/comments/{ commentId }', function ($params) use (&$count) {
            $count++;

            $this->assertInstanceOf(RouteParams::class, $params);
            $this->assertSame('123', $params->postId);
            $this->assertSame('abc', $params->commentId);
        });
        $router->match($request);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function can_add_regex_constraints_on_params_as_key_value()
    {
        $matchingRequest = new ServerRequest([], [], '/posts/123/comments', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments', 'GET');
        $router = new Router;

        $route = $router->get('/posts/{postId}/comments', function () use (&$count) {
            $count++;
        })->where('postId', '[0-9]+');

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function can_add_multiple_regex_constraints_on_params_as_key_value()
    {
        $matchingRequest = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments/123', 'GET');
        $router = new Router;

        $route = $router->get('/posts/{postId}/comments/{commentId}', function () use (&$count) {
            $count++;
        })->where('postId', '[0-9]+')->where('commentId', '[a-z]+');

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function can_add_regex_constraints_on_params_as_array()
    {
        $matchingRequest = new ServerRequest([], [], '/posts/123/comments', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments', 'GET');
        $router = new Router;

        $route = $router->get('/posts/{postId}/comments', function () use (&$count) {
            $count++;
        })->where(['postId' => '[0-9]+']);

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function can_add_multiple_regex_constraints_on_params_as_array()
    {
        $matchingRequest = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments/123', 'GET');
        $router = new Router;

        $route = $router->get('/posts/{postId}/comments/{commentId}', function () use (&$count) {
            $count++;
        })->where([
            'postId' => '[0-9]+',
            'commentId' => '[a-z]+',
        ]);

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function can_provide_optional_params()
    {
        $matchingRequest1 = new ServerRequest([], [], '/posts/123', 'GET');
        $matchingRequest2 = new ServerRequest([], [], '/posts', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments', 'GET');
        $router = new Router;

        $count = 0;

        $route = $router->get('/posts/{postId?}', function ($postId) use (&$count) {
            $count++;
        });

        $router->match($matchingRequest1);
        $router->match($matchingRequest2);
        $router->match($nonMatchingRequest);

        $this->assertSame(2, $count);
    }

    /** @test */
    public function can_generate_canonical_uri_with_trailing_slash_for_named_route()
    {
        $router = new Router;

        $route = $router->get('/posts/all', function () {})->name('test.name');

        $this->assertSame('/posts/all/', $router->url('test.name'));
    }

    /** @test */
    public function can_generate_canonical_uri_with_trailing_slash_for_named_route_with_params()
    {
        $router = new Router;

        $route = $router->get('/posts/{id}/comments', function () {})->name('test.name');

        $this->assertSame('/posts/123/comments/', $router->url('test.name', ['id' => 123]));
    }

    /** @test */
    public function url_throws_exception_when_provided_params_fail_the_regex_constraints()
    {
        $this->expectException(RouteParamFailedConstraintException::class);

        $router = new Router;

        $route = $router->get('/posts/{id}/comments', function () {})
            ->name('test.name')
            ->where('id', '[a-z]+');

        $router->url('test.name', ['id' => 123]);
    }

    /** @test */
    public function generating_a_url_for_a_named_route_that_doesnt_exist_throws_an_exception()
    {
        $this->expectException(NamedRouteNotFoundException::class);

        $router = new Router;

        $router->url('test.name');
    }

    /** @test */
    public function can_generate_canonical_uri_after_match_has_been_called()
    {
        $router = new Router;

        $route = $router->get('/posts/all', function () {})->name('test.name');
        $request = new ServerRequest([], [], '/does/not/match', 'GET');
        $router->match($request, 'GET');

        $this->assertSame('/posts/all/', $router->url('test.name'));
    }

    /** @test */
    public function adding_routes_after_calling_url_throws_an_exception()
    {
        $this->expectException(TooLateToAddNewRouteException::class);

        $router = new Router;

        $route = $router->get('posts/all', function () {})->name('test.name');
        $router->url('test.name');

        $route = $router->get('another/url', function () {});
    }

    /** @test */
    public function adding_routes_after_calling_match_throws_an_exception()
    {
        $this->expectException(TooLateToAddNewRouteException::class);

        $request = new ServerRequest([], [], '/posts/all', 'GET');
        $router = new Router;

        $route = $router->get('posts/all', function () {});
        $router->match($request);

        $route = $router->get('another/url', function () {});
    }

    /** @test */
    public function can_add_routes_in_a_group()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router = new Router;
        $count = 0;

        $router->group('prefix', function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_add_routes_in_a_group_using_array_as_first_param()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router = new Router;
        $count = 0;

        $router->group(['prefix' => 'prefix'], function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_add_routes_in_a_group_using_array_as_first_param_with_no_prefix()
    {
        $request = new ServerRequest([], [], '/all', 'GET');
        $router = new Router;
        $count = 0;

        $router->group([], function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function group_prefixes_work_with_leading_slash()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router = new Router;
        $count = 0;

        $router->group('/prefix', function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function group_prefixes_work_with_trailing_slash()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router = new Router;
        $count = 0;

        $router->group('prefix/', function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_add_routes_in_nested_groups()
    {
        $request = new ServerRequest([], [], '/prefix/prefix2/all', 'GET');
        $router = new Router;
        $count = 0;

        $router->group('prefix', function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->group('prefix2', function ($group) use (&$count) {
                $count++;
                $this->assertInstanceOf(RouteGroup::class, $group);

                $group->get('all', function () {
                    return 'abc123';
                });
            });
        });
        $response = $router->match($request);

        $this->assertSame(2, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_add_routes_in_nested_groups_with_array_syntax()
    {
        $request = new ServerRequest([], [], '/prefix/prefix2/all', 'GET');
        $router = new Router;
        $count = 0;

        $router->group(['prefix' => 'prefix'], function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->group(['prefix' => 'prefix2'], function ($group) use (&$count) {
                $count++;
                $this->assertInstanceOf(RouteGroup::class, $group);

                $group->get('all', function () {
                    return 'abc123';
                });
            });
        });
        $response = $router->match($request);

        $this->assertSame(2, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_set_base_path()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router = new Router;
        $router->setBasePath('/base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_set_base_path_without_trailing_slash()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router = new Router;
        $router->setBasePath('/base-path');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_set_base_path_without_leading_slash()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router = new Router;
        $router->setBasePath('base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_set_base_path_without_leading_or_trailing_slash()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router = new Router;
        $router->setBasePath('base-path');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function can_update_base_path_after_match_has_been_called()
    {
        $router = new Router;
        $router->setBasePath('/base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });

        $request1 = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $response1 = $router->match($request1);

        $router->setBasePath('/updated-base-path/');

        $request2 = new ServerRequest([], [], '/updated-base-path/prefix/all', 'GET');
        $response2 = $router->match($request2);

        $this->assertSame(2, $count);
        $this->assertSame('abc123', $response1->getBody()->getContents());
        $this->assertSame('abc123', $response2->getBody()->getContents());
    }

    /** @test */
    public function can_add_middleware_as_a_closure_to_a_route()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        })->middleware(function (ServerRequestInterface $request, callable $next) use (&$count) {
            $count++;

            $response = $next($request);
            return $response->withHeader('X-key', 'value');
        });
        $response = $router->match($request);

        $this->assertSame(2, $count);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('X-key'));
        $this->assertSame('value', $response->getHeader('X-key')[0]);
    }

    /** @test */
    public function can_get_currently_matched_route()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });

        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame($route, $router->currentRoute());
    }

    /** @test */
    public function can_get_currently_matched_route_name()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;
        $count = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        })->name('test123');

        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('test123', $router->currentRouteName());
    }

    /** @test */
    public function current_route_name_returns_null_when_match_not_yet_called()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;

        $route = $router->get('/test/123', function () {
            return 'abc123';
        })->name('test123');

        $this->assertSame(null, $router->currentRouteName());
    }

    /** @test */
    public function current_route_name_returns_null_when_matched_route_has_no_name()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router = new Router;

        $route = $router->get('/test/123', function () {
            return 'abc123';
        });

        $response = $router->match($request);

        $this->assertSame(null, $router->currentRouteName());
    }

    /** @test */
    public function can_get_list_of_registered_routes()
    {
        $router = new Router;
        $route1 = $router->get('/test/123', function () {});
        $route2 = $router->get('/test/456', function () {});

        $routes = $router->getRoutes();

        $this->assertCount(2, $routes);
        $this->assertContains($route1, $routes);
        $this->assertContains($route2, $routes);
    }

    /**
     * @test
     */
    public function can_extend_post_behaviour_with_macros()
    {
        Router::macro('testFunctionAddedByMacro', function () {
            return 'abc123';
        });

        $queryBuilder = new Router();

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMacro());
        $this->assertSame('abc123', Router::testFunctionAddedByMacro());
    }

    /**
     * @test
     */
    public function can_extend_post_behaviour_with_mixin()
    {
        Router::mixin(new RouterMixin);

        $queryBuilder = new Router();

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMixin());
    }
}

class RouterMixin
{
    function testFunctionAddedByMixin()
    {
        return function() {
            return 'abc123';
        };
    }
}
