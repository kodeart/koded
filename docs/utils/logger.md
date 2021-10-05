Logging (PSR-5)
=======

Default logging library is [Koded Logging][koded-log].

!!! info "Logging"
    [![cache-log-status]][cache-log-package]

    Demand an instance of PSR-5 `Psr\Log\LoggerInterface` in your methods,
    DI container will inject the configured log instance.

```php
<?php

...
  public function something(LoggerInterface $log) {
    // ...
  }
...
```

Setup logging
-------------

Logger library is done in the [application configuration](../configure/index.md#config)
for the `App` instance.

```php
<?php

return [
  'logging' => [
    'timezone' => 'UTC',
    'dateformat' => 'Y-m-d H:i:s.u'
    'loggers' => [
      [
        'class' => \Koded\Logging\Processors\Cli::class,
        'format' => '[levelname] message',
        'levels' => -1
      ]
    ]
  ],
];
```

The conf key `loggers` is a list of `Koded\Logging\Log` 
log processor implementations. By default only one log
processor is registered that processes all log levels.


[koded-log]: https://github.com/kodedphp/logging
[cache-log-status]: https://img.shields.io/packagist/v/koded/logging.svg
[cache-log-package]: https://packagist.org/packages/koded/logging