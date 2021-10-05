The Router
==========

URI routing in **Koded** is easy. When dealing with routes keep in mind the following

  - the router does not care about the HTTP method for the endpoint 
  (this is resolved internally by the `App` instance)
  - the HTTP method for the route (endpoint) is implemented **in the resource** object (or function)
  - this framework does not offer a _"Route"_ object
  - parsed [URI parameters](parameters.md) are stored in the `Psr\Http\Message\ServerRequestInterface@attributes`
    with their corresponding `name => value` pairs
  - [all routes are cached](caching.md) for better performance


Routes are implemented in your execution script(s), ie. `index.php`,
or any other PHP file that is part of the HTTP request/URI.

route() method
--------------

```php
<?php

public function route(
  string $uriTemplate,
  object|string $resource,
  array $middleware = [],
  bool $explicit = false): App;
```

### `$uriTemplate` 
**REQUIRED** - a route string that should match the request URI.

See [URI parameters](parameters.md) how to deal with named variables
in the URI template.

The parameters are optional, you can normally set direct links, ie.

```php
<?php
$app->route('/about', About::class);
```

### `$resource`
**REQUIRED** - your resolver class (aka resource) where the matching HTTP methods are implemented.

This argument can be a FQCN, an instance of a class, a `\Closure`, or PHP callable.

```php
<?php

$app
    ->route('/one', ResourceOne::class)
    ->route('/two', new ResourceTwo)
    ->route('/three', function(Request $req, Response $res): Response {
        // ...
    })
    ->route('/four', 'ResourceFour::methodName')
    ->route('/five', 'some_function')
```

### `$middleware`
is a list of middleware classes/objects for this particular route.

This argument solves the need to attach any functionalities 
with middlewares to the route. A good example is to provide an
`AuthMiddleware` to protect the route(s) with 
authentication/authorization logic:

```php
<?php
$app->route('/protected', Resource::class, [AuthMiddleware::class]);
```

### `$explicit`
if set to `TRUE` it will override the global middleware stack (`App::$middleware`)
and use only the middlewares in the `$middleware` list.

In this example no middleware runs for this route, because `$explicit = true`
and the `$middleware = []` is empty:

```php
<?php
$app->route('/', Resource::class, [], true);
```

group() method
--------------

This method adds a prefix to all routes in the `$routes` list.

```php
<?php

public function group(
  string $prefix,
  array $routes,
  array $middleware = []): App;
```

### `$prefix`
this prefix is prepended to all `$uriTemplate` strings in the `$routes` list
```php
<?php

$app->group('/v2', [
    ['/read', Resource::class],
    ['/edit', Resource::class],
    ['/add', Resource::class],
    ['/delete', Resource::class],
]);

/*
 * results in:
 * 
 * /v2/read
 * /v2/edit
 * /v2/add
 * /v2/delete
 */
```

### `$routes`

is a list of [routes](#route-method) arrays where
the array elements must match the `route()` signature.

### `$middleware`

This list of middlewares is an **additional list** that is 
merged with each route in the `$routes` list and differ from the 
`$middleware` list in the [route method](#route-method) in this manner.
