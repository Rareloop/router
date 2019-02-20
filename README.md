# Rare Router
[![Latest Stable Version](https://poser.pugx.org/rareloop/router/v/stable)](https://packagist.org/packages/rareloop/router)
![CI](https://travis-ci.org/Rareloop/router.svg?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/Rareloop/router/badge.svg)](https://coveralls.io/github/Rareloop/router)

A simple PHP router built on [AltoRouter](https://github.com/dannyvankooten/AltoRouter) but inspired by the [Laravel](https://laravel.com/docs/5.4/routing) API.

## Installation

```
composer require rareloop/router
```

## Usage

### Creating Routes

#### Map

Creating a route is done using the `map` function:

```php
use Rareloop\Router\Route;

$router = new Router;

// Creates a route that matches the uri `/posts/list` both GET 
// and POST requests. 
$router->map(['GET', 'POST'], 'posts/list', function () {
    return 'Hello World';
});
```

`map()` takes 3 parameters:

- `methods` (array): list of matching request methods, valid values:
    + `GET`
    + `POST`
    + `PUT`
    + `PATCH`
    + `DELETE`
    + `OPTIONS`
- `uri` (string): The URI to match against
- `action`  (function|string): Either a closure or a Controller string

#### Route Parameters
Parameters can be defined on routes using the `{keyName}` syntax. When a route matches that contains parameters, an instance of the `RouteParams` object is passed to the action.

```php
$router->map(['GET'], 'posts/{id}', function(RouteParams $params) {
    return $params->id;
});
```

If you need to add constraints to a parameter you can pass a regular expression pattern to the `where()` function of the defined `Route`:

```php
$router->map(['GET'], 'posts/{id}/comments/{commentKey}', function(RouteParams $params) {
    return $params->id;
})->where('id', '[0-9]+')->where('commentKey', '[a-zA-Z]+');

// or

$router->map(['GET'], 'posts/{id}/comments/{commentKey}', function(RouteParams $params) {
    return $params->id;
})->where([
    'id', '[0-9]+',
    'commentKey', '[a-zA-Z]+',
]);
```

#### Optional route Parameters
Sometimes your route parameters needs to be optional, in this case you can add a `?` after the parameter name:

```php
$router->map(['GET'], 'posts/{id?}', function(RouteParams $params) {
    if (isset($params->id)) {
        // Param provided
    } else {
        // Param not provided
    }
});
```


#### Named Routes
Routes can be named so that their URL can be generated programatically:

```php
$router->map(['GET'], 'posts/all', function () {})->name('posts.index');

$url = $router->url('posts.index');
```

If the route requires parameters you can be pass an associative array as a second parameter:

```php
$router->map(['GET'], 'posts/{id}', function () {})->name('posts.show');

$url = $router->url('posts.show', ['id' => 123]);
```

If a passed in parameter fails the regex constraint applied, a `RouteParamFailedConstraintException` will be thrown.

#### HTTP Verb Shortcuts
Typically you only need to allow one HTTP verb for a route, for these cases the following shortcuts can be used:

```php
$router->get('test/route', function () {});
$router->post('test/route', function () {});
$router->put('test/route', function () {});
$router->patch('test/route', function () {});
$router->delete('test/route', function () {});
$router->options('test/route', function () {});
```

#### Setting the basepath
The router assumes you're working from the route of a domain. If this is not the case you can set the base path:

```php
$router->setBasePath('base/path');
$router->map(['GET'], 'route/uri', function () {}); // `/base/path/route/uri`
```

#### Controllers
If you'd rather use a class to group related route actions together you can pass a Controller String to `map()` instead of a closure. The string takes the format `{name of class}@{name of method}`. It is important that you use the complete namespace with the class name.

Example:

```php
// TestController.php
namespace \MyNamespace;

class TestController
{
    public function testMethod()
    {
        return 'Hello World';
    }
}

// routes.php
$router->map(['GET'], 'route/uri', '\MyNamespace\TestController@testMethod');
```

### Creating Groups
It is common to group similar routes behind a common prefix. This can be achieved using Route Groups:

```php
$router->group('prefix', function ($group) {
    $group->map(['GET'], 'route1', function () {}); // `/prefix/route1`
    $group->map(['GET'], 'route2', function () {}); // `/prefix/route2§`
});
```

### Middleware
PSR-15/7 Middleware can be added to both routes and groups.

#### Adding Middleware to a route
At it's simplest, adding Middleware to a route can be done by passing an object to the `middleware()` function:

```php
$middleware = new AddHeaderMiddleware('X-Key1', 'abc');

$router->get('route/uri', '\MyNamespace\TestController@testMethod')->middleware($middleware);
```

Multiple middleware can be added by passing more params to the `middleware()` function:

```php
$header = new AddHeaderMiddleware('X-Key1', 'abc');
$auth = new AuthMiddleware();

$router->get('route/uri', '\MyNamespace\TestController@testMethod')->middleware($header, $auth);
```

Or alternatively, you can also pass an array of middleware:

```php
$header = new AddHeaderMiddleware('X-Key1', 'abc');
$auth = new AuthMiddleware();

$router->get('route/uri', '\MyNamespace\TestController@testMethod')->middleware([$header, $auth]);
```

#### Adding Middleware to a group
Middleware can also be added to a group. To do so you need to pass an array as the first parameter of the `group()` function instead of a string.

```php
$header = new AddHeaderMiddleware('X-Key1', 'abc');

$router->group(['prefix' => 'my-prefix', 'middleware' => $header]), function ($group) {
    $group->map(['GET'], 'route1', function () {}); // `/my-prefix/route1`
    $group->map(['GET'], 'route2', function () {}); // `/my-prefix/route2§`
});
```

You can also pass an array of middleware if you need more than one:

```php
$header = new AddHeaderMiddleware('X-Key1', 'abc');
$auth = new AuthMiddleware();

$router->group(['prefix' => 'my-prefix', 'middleware' => [$header, $auth]]), function ($group) {
    $group->map(['GET'], 'route1', function () {}); // `/my-prefix/route1`
    $group->map(['GET'], 'route2', function () {}); // `/my-prefix/route2§`
});
```

#### Defining Middleware on Controllers
You can also apply Middleware on a Controller class too. In order to do this your Controller must extend the `Rareloop\Router\Controller` base class.

Middleware is added by calling the `middleware()` function in your Controller's `__constructor()`.

```php
use Rareloop\Router\Controller;

class MyController extends Controller
{
    public function __construct()
    {
        // Add one at a time
        $this->middleware(new AddHeaderMiddleware('X-Key1', 'abc'));
        $this->middleware(new AuthMiddleware());

        // Add multiple with one method call
        $this->middleware([
            new AddHeaderMiddleware('X-Key1', 'abc',
            new AuthMiddleware(),
        ]);
    }
}
```

By default all Middleware added via a Controller will affect all methods on that class. To limit what methods Middleware applies to you can use `only()` and `except()`:

```php
use Rareloop\Router\Controller;

class MyController extends Controller
{
    public function __construct()
    {
        // Only apply to `send()` method
        $this->middleware(new AddHeaderMiddleware('X-Key1', 'abc'))->only('send');

        // Apply to all methods except `show()` method
        $this->middleware(new AuthMiddleware())->except('show');

        // Multiple methods can be provided in an array to both methods
        $this->middleware(new AuthMiddleware())->except(['send', 'show']);
    }
}
```

### Matching Routes to Requests
Once you have routes defined, you can attempt to match your current request against them using the `match()` function. `match()` accepts an instance of Symfony's `Request` and returns an instance of Symfony's `Response`:

```php
$request = Request::createFromGlobals();
$response = $router->match($request);
$response->send();
```

#### Return values
If you return an instance of `Response` from your closure it will be sent back un-touched. If however you return something else, it will be wrapped in an instance of `Response` with your return value as the content.

#### Responsable objects 
If you return an object from your closure that implements the `Responsable` interface, it's `toResponse()` object will be automatically called for you.

```php
class MyObject implements Responsable
{
    public function toResponse(RequestInterface $request) : ResponseInterface
    {
        return new TextResponse('Hello World!');
    }
}

$router->get('test/route', function () {
    return new MyObject();
});
```

#### 404
If no route matches the request, a `Response` object will be returned with it's status code set to `404`;

#### Accessing current route
The currently matched `Route` can be retrieved by calling:

```php
$route = $router->currentRoute();
```

If no route matches or `match()` has not been called, `null` will be returned.

You can also access the name of the currently matched `Route` by calling:

```php
$name = $router->currentRouteName();
```

If no route matches or `match()` has not been called or the matched route has no name, `null` will be returned.

### Using with a Dependency Injection Container
The router can also be used with a PSR-11 compatible Container of your choosing. This allows you to type hint dependencies in your route closures or Controller methods.

To make use of a container, simply pass it as a parameter to the Router's constructor:

```php
use MyNamespace\Container;
use Rareloop\Router\Router;

$container = new Container();
$router = new Router($container);
```

After which, your route closures and Controller methods will be automatically type hinted:

```php
$container = new Container();

$testServiceInstance = new TestService();
$container->set(TestService::class, $testServiceInstance);

$router = new Router($container);

$router->get('/my/route', function (TestService $service) {
    // $service is now the same object as $testServiceInstance
});
```
