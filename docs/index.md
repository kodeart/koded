The Koded Web Framework
=======================

Why?
---

Sometimes we want a prototype, or quick/simple operations,
or solutions for building fast web APIs and app backends. 
And having a full-blown frameworks will eventually weight you down
with tons of unnecessary abstractions and dependencies.

Koded offers small footprint with a clean design that follows
the HTTP and ReST architectural style. It tends to be very fast 
and easy to work with.

Minimum application
-------------------

```php
<?php
use Koded\Framework\App;
use Koded\Http\ServerResponse as R;

require __DIR__ . '/../vendor/autoload.php';

((new App)
    ->route('/', fn() => new R('Hello'))
)();
```

This fits in a tweet.

Resource Method Representation
------------------------------

Koded Framework follows the **RMR web architectural pattern**,
but the developer is free to use whatever it prefers and does 
not enforce any programming patterns in the application.

However, it is strongly recommended adopting and using RMR 
because it is aligned nicely with the HTTP ReST concepts.
After all, web apps needs fundamental directions and principles,
not "perfect" solutions so RMR fits here very well.

=== "Resource"  
    
    An **object** in the RESTful system identified by a URL that exposes
    methods that corresponds to the standard HTTP methods (GET, POST, PUT, etc)
    i.e. a business object (entity).

=== "Method"  
    
    The **HTTP request method** that corresponds a **Resource** method that is
    executed by the request/URL, which returns a **Representation** for that
    resource.

=== "Representation"  
    
    Provides a **Resource** to clients in a readable format (JSON, XML, PDF, HTML, etc).
    It is the payload of the response object (processed by the HTTP **Method** 
    that is sent back to the client).
