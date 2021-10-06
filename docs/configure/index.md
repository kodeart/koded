App class configuration
=======================

The configuration object is created by setting the config
directives in the `Koded\Framework\App` constructor.

| Argument          | Type           | Required | Default | |
|-------------------|----------------|----------|---------|------------|
| [modules][1]      | array          | no       | []      | A list of DIModules for the app |
| [middleware][2]   | array          | no       | []      | The app middleware stack |
| [config][3]       | object, string | no       | ''      | A path to a config file, FQCN of the configuration class, or an instance of a config object |
| [renderer][4]     | string         | no       | 'start_response' | Response renderer function/method |

All directives are optional.

```php
<?php

new App(
    modules: [
        // a list of DIModule implementation(s)
    ],
    middleware: [
        // a list of PSR-15 middleware classes (global)
    ],
    config: __DIR__ . '/path/to/my/config.php', 
    // or
    config: '.env', 
    // or
    config: new MyConfig(),

    renderer: MyRenderer::class,
    // or
    renderer: 'my_renderer_function'
);
```

Constructor arguments
---------------------

### modules

!!! abstract "optional"
    This argument accepts a list of `DIModule` instances that 
    configures the Dependency Injection Container for your app.
    [See more about DI modules](modules.md).

    Example:
    ``` php
    [
        new My\App\Module(),
    ]
    ```

### middleware

!!! abstract "optional"
    A list of [PSR-15 middleware][5] classes for your application.

    Example:
    ``` php
    [
        // ... your middleware classes
        My\App\Middleware\CustomMiddleware::class,
        new My\App\Middleware\Other(),
    ]
    ```
Check the [PSR-15 middleware stack][6] page for details how **Koded** framework
implements this functionality.

### config

!!! abstract "optional"

    Configuration values for your application. It supports 

    - `.php` file that returns an array, or 
    - `.env` file, or 
    - instance of `Koded\Stdlib\Configuration` object.

    Examples:

    ``` php
    <?php

    # conf.php
    return [
        'key' => 'value',
    ];

    # /var/www/public/index.php
    new App(config: '/path/to/conf.php');
    ```

    ``` ini
    # .env is always loaded (if exist)
    key=value
    ```

    ``` php
    <?php
    use Koded\Framework\App;
    use Koded\Stdlib\{Config, Immutable}
    
    $config = new Config('/path/to/app/dir', new Immutable([
        'key' => 'value',
    ]));
    
    new App(config: $config);
    ```

!!! warning ".env support"

[comment]: <> (    Koded loads `.env` file by default. No need to set it)

[comment]: <> (    in the `App` constructor - unless it is located outside the)

[comment]: <> (    application root folder.  )

    **Make sure .env file is not accessible from the outside.**

### renderer

!!! abstract "optional"

    A custom renderer for the processed `ResponseInterface` object. 
    This method/function is executed by the DI container, meaning the 
    depencencies can be anything that the container is able to resolve.

    **default** `'start_response'` 

    ``` php
    <?php
    new App(renderer: MyCustomRenderer::class);

    new App(renderer: 'My\App\custom_renderer');
    ```

The purpose of this class method (or function) is to provide a custom
processing of the `ServerRequestInterface`  and `ResponseInterface` objects
BEFORE it is finally sent to the client, for example a custom-made HTML
renderer for server-side template engines, or a response streaming, etc.


[1]: #modules
[2]: #middleware
[3]: #config
[4]: #renderer
[5]: ../middleware/builtin/
[6]: ../middleware/psr-15/
