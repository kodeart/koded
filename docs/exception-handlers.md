Exception handling
==================

By default, the following exceptions are caught and handled

 - `\Exception`
 - `\Error`
 - `Koded\Framework\HTTPError`

!!! info ""
    The error response payload follows the [RFC-7807][rfc-7807]{: target="_blank" .external } specification.

If you wish to capture specific exception types and/or return custom 
error messages, create your own class and register it with the method 
`App::withErrorHandler()` to convert the exceptions into the desired HTTP responses.

!!! abstract "handle() method signature"
    `handle(request, response, exception): void`

If app does not use 3rd party PSR-7 library, then Koded will use its 
own implementations from the [Koded HTTP][koded-http] library.

```php
<?php

# CustomExceptionHandler.php

use Koded\Framework\{App, HTTPError};
use Koded\Http\Interfaces\{Request, Response};

class CustomExceptionHandler {

    public static function handle(
        Request $request,
        Response $response,
        HTTPError $exception): void
    {
        // do something with $exception
        // i.e. re-format the error message and set it in the response
    }
}
```

Register handler
----------------

!!! warning "Register before routes"
    Order matters. It is required to register the custom 
    exception handlers BEFORE the routes, otherwise they 
    won't be handled if the request/route has been resolved.

```php  hl_lines="6"
<?php

# index.php

((new App)
    ->withErrorHandler(CustomExceptionHandler::class)
    ->route(/* ... */)
)();
```

This implementation is using a  **PSR-7** compatible 
library and a standard PHP `Throwable` class.

```php
<?php

use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};

class PDOExceptionHandler {

    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \Throwable $exception): void
    {
        if ($exception instanceof \PDOException) {
            // do something about it
        }
    }
}
```

Unregister handler
------------------

Use `App::withoutErrorHandler()` method to remove the error handler.

!!! warning "Unregister after custom handlers"
Order matters. Unregister the exception handlers AFTER
the custom registration, otherwise they won't be removed.

```php  hl_lines="8"
<?php

# index.php

((new App)
    ->withErrorHandler(SomeCustomErrorHandler::class)
    
    ->withoutErrorHandler(AnotherCustomExceptionHandler::class)
    ->route(/* ... */)
)();
```


[rfc-7807]: https://tools.ietf.org/html/rfc7807
[koded-http]: https://github.com/kodedphp/http
