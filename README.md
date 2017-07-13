# Rare Router
![CI](https://travis-ci.org/Rareloop/router.svg?branch=master)

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

### Matching Routes to Requests
Once you have routes defined, you can attempt to match your current request against them using the `match()` function. `match()` accepts an instance of Symfony's `Request` and returns an instance of Symfony's `Response`:

```php
$request = Request::createFromGlobals();
$response = $router->match($request);
$response->send();
```

If you return an instance of `Response` from your closure it will be sent back un-touched. If however you return something else, it will be wrapped in an instance of `Response` with your return value as the content.

#### 404
If no route matches the request, a `Response` object will be returned with it's status code set to `404`;
