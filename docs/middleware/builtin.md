Built-in middlewares
====================

Out of the box **Koded** offers a curated list with 
common middleware functionality. The developers are
encouraged to create their own (or use existing) PSR-15
middlewares to enhance the application features.

CorsMiddleware
--------------

(**loaded by default**)  
Cross-Origin Request Sharing support for your JavaScript 
applications.

Please check the [CORS middleware](cors.md) page to customize the behaviour
of this middleware class.

GzipMiddleware
--------------

(**loaded by default**)  
Compresses the response payload with `gzencode`.


AuthMiddleware
--------------

A basic mechanism to run the auth logic. Supports the `Authorization` header. 

CallableMiddleware
------------------

Used internally by the framework to support closures
for the responders, or any `PHP callable` resource.


HSTSMiddleware
--------------

HTTP Strict Transport Security (HSTS).
Redirects the request URI from HTTP to HTTPS.


NoCacheMiddleware
-----------------

Adds no-cache response headers.


XPoweredByMiddleware
-------------------

Sets the `X-Powered-By` response header.
