Requirements
============

[![php-version]][php-link] [![license-status]][license-link]

Installation
------------

### Using composer

```
composer require koded/framework
```

```json
{
    "require": {
        "koded/framework": "^1"
    }
}
```

App basics
----------

### Files structure

**It is up to you how you're going to structure your project.**
A simple and clear structuring is essential for great development,
on a along run (or short too), but that is up to developer needs, 
or based on the app complexity, team decisions, or various other reasons.

Let's look at something that is good in general as a startup,

```
app/
    .env
public/
    .htaccess
    index.php
vendor/
composer.json
```

Everything regarding your application goes into the `app/` folder.
This is an example, `app` is not a requirement and it can be anything you want.

!!! warning "Protect your code!"
    It is important to keep everything outside the `public/` folder
    (`app/`, `vendor/` or anything that is app related and may expose the code).
    Make sure the app code is not accessible from the outside.

### App entry point

The simplest way to start is to create the "entry script" for all
HTTP requests. There we create an isntance of `App` and define the URI routes.

``` php
# /path/to/public/index.php

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

From here on add more routes to your API, but keep in mind that
^^using closures as resources is NOT the recommended way^^ to 
build the application. For more on this please follow the documentation further.


[php-version]: https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg
[php-link]: https://php.net/
[license-status]: https://img.shields.io/badge/License-BSD%203--Clause-blue.svg
[license-link]: https://github.com/kodeart/koded/LICENSE