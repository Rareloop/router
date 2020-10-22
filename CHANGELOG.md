# Changelog

v4.4.0
- Switch from the deprecated `zendframework/zend-diactoros` to `laminas/laminas-diactoros`
- Increase minimum PHP version to 7.1

v4.3.1
- Fix errors when running PHP 7.4

v4.3.0
- Add support for a custom Middleware Resolver

v4.2.0
- Add support for Middleware defined on Controllers

v4.1.0
- Made `Router`, `Route` & `RouteGroup` Macroable

v4.0.0
- Deprecated `http-interop/http-server-middleware` in favour of the official PSR-15 interfaces found in `psr/http-server-middleware`

v3.2.1
- Fix `getActionName()` output

v3.2.0
- Add `getRoutes()` method to Router
- Add `getActionName()` method to Route

v3.1.0
- Add support for optional route parameters
- Add ability to apply a regex constraint on route parameters

v3.0.1
- Fix routes with a leading `/` when added to a group

v3.0.0
- Add the `Responsable` interface and auto convert to `Response`'s as part of the `match()` process.

v2.1.0
- Add `currentRoute()` method to access the currently matched Route
- Add `currentRouteName()` method to access the currently matched Route name

v2.0.0
- Add Dependency Injection and Middleware support

v1.0.0
- Initial release
