CORS middleware
===============

Use the configuration to set the desired behavior of
your application CORS middleware.


```php

# config.php

<?php

return [
  'cors.origin'  => '',
  'cors.methods' => '',
  'cors.headers' => '',
  'cors.expose'  => 'Authorization, X-Forwarded-With',
  'cors.maxAge'  => 0,
  'cors.disable' => false,
  ...
];
```

### Configuration directives

CORS config directives will set the **global behavior** of the middleware. For example,
if `cors.methods` has a value of `GET, POST` then only these two methods are allowed
for **all CORS requests**. The same applies to all settings.

| Directive    | Type   | Default                         |                                                      |
|:-------------|--------|---------------------------------|------------------------------------------------------|
| cors.origin  | string | (empty)                         | The server origin address as in `schema://host:port` (if the port is not a standard port) |
| cors.methods | string | (empty)                         | Comma-separated list of supported HTTP methods |
| cors.headers | string | (empty)                         | Comma-separated list of supported headers |
| cors.expose  | string | Authorization, X-Forwarded-With | Comma-separated list of exposed headers |
| cors.maxAge  | int    | 0                               | `max-age` header for OPTIONS request (before the actual request is called) |
| cors.disable | bool   | false                           | This directive will completely disable the middleware |
