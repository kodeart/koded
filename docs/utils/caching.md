Application caching (PSR-16)
===================

The (app) caching is bolted-in and always on. The default
cache library is [Koded Simple Cache][koded-simple-cache].

!!! info "Application caching"
    [![cache-simple-status]][cache-simple-package]

    The cache functionality is available in the user app by 
    simply demanding an instance of PSR-16 `Psr\SimpleCache\CacheInterface`
    instance. The DI container will inject the configured cache client.

```php 
<?php

...
  public function something(CacheInterface $cache) {
    // ...
  }
...
```

Setup cache
-----------

Caching configuration is done in the [application configuration](../configure/index.md#config)
for the `App` instance.

```php
<?php

return [
    'caching' => [
        //
    ]
];
```

```php
<?php

new App(config: 'config.php');
```

### Memory
Default cache is `Memory` and does not require a special treatment.
It will provide caching for the duration of the request which is
useful for development and unit testing.

!!! abstract "Memory (default)"
    No need to set anything in the configuration.
    This is the default caching client.

<!-- ### Redis
!!! abstract "Redis"

### Memcached
!!! abstract "Memcached"

### File
!!! abstract "File" -->


[koded-simple-cache]: https://github.com/kodedphp/cache-simple
[cache-simple-status]: https://img.shields.io/packagist/v/koded/cache-simple.svg
[cache-simple-package]: https://packagist.org/packages/koded/simple-cache
