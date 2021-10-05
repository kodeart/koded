Middleware stack
================

The PSR-15 method signature for processing 
the incoming server request is very simple:

    process(request, handler): response

`request` is the server side incoming request object, `handler` is the
object that receive the request, do some work and returns a `response`.

It is important to keep in mind when creating a custom middleware
class for your app, **where** the processing of the `ServerRequestInterface` 
and `ResponseInterface` instances will take place.

### Workflow

It is an "onion layer".

```
request
    MW1 (request)
        MW2 (request)
            MW3 (request)
                    ((RESOURCE))
            MW3 (response)
        MW2 (response)
    MW1 (response)
response
```

 1. when **request** object (`ServerRequestInterface`) is passed into the 
    first middleware in the stack, it **propagates forward** through all 
    middleware classes by modifying the instance,
     
 2. up to the point where the middleware classes are exhausted, 
    then request object enters the **Resource**
    
 3. at this point the request object has been modified 
    by all middleware classes in the stack

 4. once the `Resource` object return the `ResponseInterface` instance,
    it continue to **propagate backwards** through the middleware stack, 
    now modifying the **response** object

 5. and finally response exits the middleware stack, to be processed 
    by the Koded renderer and sent back to the caller (ex. browser)

### Example

```php
<?php

namespace My\App\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class MyMiddleware implements MiddlewareInterface {

    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler): ResponseInterface
    {
        // --> Everything here is about the $request object
        
        $response = $handler->handle($request);
        
        // <-- Everything here is about the $response object
        
        return $response;
    }
}
```

Lets set a random string for the request and pass it in the response header:

```php
<?php

namespace My\App\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class RequestIdMiddleware implements MiddlewareInterface {

    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request
            ->withAttribute('req-id', bin2hex(random_bytes(8)));

        // [NOTE]: the next middleware (or the resource object)
        // will have the ID in the request "attributes" property 

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-Req-ID', $request->getAttribute('req-id'));
    }
}
```
