DIModule interface
==================

    The purpose of this feature is to setup the DI container in your app.

Once your app stops being "Hello World" and assuming you are going to
follow some SOLID principles (or maybe not), the application may end 
up with lots of interfaces and concrete implementations. All of them 
can be mapped in the app module.

[Koded Container][koded-container] [![koded-container-status]][koded-container-package]
supports multiple modules, but **one is sufficient** for the container's configuration.
Again, it is up to you how you are going to structure the application,
so sometimes it makes sense to create multiple modules.

Lets say you want to provide a database for the auth backend instance.
Because Koded already has one registered by default (`Koded\Framework\Auth\SessionAuthBackend`)
you can easily configure it in your DI module.

```php
<?php

# /opt/my-app/MyAppModule.php

namespace My\App;

use Koded\{DIContainer, DIModule};
use Koded\Framework\Auth\AuthBackend;
use My\App\Auth\DatabaseAuth;

class MyAppModule implements DIModule {

    public function configure(DIContainer $container): void
    {
        $container->bind(AuthBackend::class, DatabaseAuth::class);
    }
}
```

Now register the module in the `App` constructor

``` php
<?php

# index.php

new App(modules: [new MyAppModule]);
```

As you can see it's very easy to override anything in the Koded framework,
including the default bindings.


[koded-container]: https://github.com/kodedphp/container
[koded-container-status]: https://img.shields.io/packagist/v/koded/container.svg
[koded-container-package]: https://packagist.org/packages/koded/container
