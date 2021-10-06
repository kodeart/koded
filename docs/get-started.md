Requirements
============

[![php-version]][php-link] [![license-status]][license-link]

Installation
------------

### Using composer

```
composer require koded/framework
```

!!! info "No composer?"
    If you don't have `composer` please follow the [download][composer]
    instructions how to install it on your OS.

    For manual install on Linux you may run this command:

    ```sh
    curl https://getcomposer.org/download/latest-stable/composer.phar -o /usr/local/bin/composer \
        && chmod +x /usr/local/bin/composer
    ```

App basics
----------

### Files structure

**It is up to you how you're going to structure your project.**
A simple and clear structuring is essential for great development,
on a long run (or short too), but that is up to developer needs, 
or based on the app complexity, team decisions, or various other reasons.

Let's look at something that is good in general as a startup,

```
app/
    .env
html/
    .htaccess
    index.php
vendor/
composer.json
```

Everything regarding your application goes into the `app/` folder.
This is an example, `app` is not a requirement and it can be anything you want.

!!! warning "Protect your code!"
    It is important to keep everything outside the `html/` folder
    (`app/`, `vendor/` or anything that is app related and may expose the code).
    Make sure the app code is not accessible from the outside.

### composer.json

A `composer.json` scaffold for your project. Run `composer update` every time
a new class is added, or use `psr-4` in `autoload` section while you develop 
the app, whatever you prefer most.

```json
{
    "require": {
        "koded/framework": "^1"
    },
    "autoload": {
      "classmap": [
        "app"
      ],
      "exclude-from-classmap": [
        "html"
      ]
    },
    "config": {
      "optimize-autoloader": true
    }
}
```

### Docker (quick example)

You can jumpstart the development with `docker` and `docker-compose`
with the above app file structure.

```yaml
# docker-compose.yaml

version: '3'

services:
    php:
        image: php:8-apache
        ports:
            - 8080:80
        volumes:
            - .:/var/www
```

Adjust the volumes, or the host port if it's already taken.
Run `docker-compose up -d` and open your browser at `127.0.0.1:8080`

### App entry point

Create the "entry script" for all HTTP requests. 
There we create an instance of `App` and define the URI routes.

``` php
# /var/www/html/index.php

<?php

use Koded\Framework\App;
use Koded\Http\Interfaces\Response;

require __DIR__ . '/../vendor/autoload.php';

((new App)
    ->route('/', function(Response $response): Response {
        $response->getBody()->write('Work In Progress...');
        return $response;
    })
)();
```

Now point your browser to app address. It should
print _"Work in Progress..."_ with status code 200.

From here on add more routes and resources to your API, but keep in mind that
^^using closures as resources is NOT the recommended way^^ to 
build the application. For more on this please follow the documentation further.


[php-version]: https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg
[php-link]: https://php.net/
[license-status]: https://img.shields.io/badge/License-BSD%203--Clause-blue.svg
[license-link]: https://github.com/kodeart/koded/LICENSE
[composer]: https://getcomposer.org/doc/00-intro.md#globally
