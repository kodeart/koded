Koded - RMR Micro Framework
===========================

[![CI](https://github.com/kodeart/koded/actions/workflows/ci.yml/badge.svg)](https://github.com/kodeart/koded/actions/workflows/ci.yml)
[![Docs](https://github.com/kodeart/koded/actions/workflows/docs.yml/badge.svg)](https://github.com/kodeart/koded/actions/workflows/docs.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/koded/framework.svg)](https://packagist.org/packages/koded/framework)
[![Code Coverage](https://scrutinizer-ci.com/g/kodeart/koded/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/kodeart/koded/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kodeart/koded/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kodeart/koded/?branch=master)


Koded is a BSD-licensed open source project for building web apps with focus on RESTful APIs.
It is designed to be between a library and framework, adoptable and easily scalable
depending on different use case scenarios.


Ecosystem
---------

Built-in functionality:

- Dependency Injection Container (PSR-11)
- URI routing (with URI templates)
- Middleware (PSR-15)
- I18n support (because the world is multilingual)
- and more

| Project                      | Status | Description
|-----------------------------:|--------|---------------------------------------------|
| [container][koded-container] | [![koded-container-status]][koded-container-package] | DI container library (PSR-11)
| [http][koded-http]           | [![koded-http-status]][koded-http-package]           | HTTP library (PSR-7, 17, 18)
| [cache][cache-simple]        | [![cache-simple-status]][cache-simple-package]       | Caching library (PSR-16)
| [session][koded-session]     | [![koded-session-status]][koded-session-package]     | Session library
| [logging][koded-logging]     | [![koded-logging-status]][koded-logging-package]     | Log facility (PSR-3)
| [i18n][koded-i18n]           | [![koded-i18n-status]][koded-i18n-package]           | Internationalization (I18n) library


Documentation
-------------

Read the docs at [Koded Framework documentation][docs] page.

[![php-version]][php-link] [![license-status]][license-link]

[docs]: https://kodeart.github.io/koded

[php-version]: https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg
[php-link]: https://php.net/
[license-status]: https://img.shields.io/badge/License-BSD%203--Clause-blue.svg
[license-link]: https://github.com/kodeart/koded/blob/master/LICENSE

[koded-container]: https://github.com/kodedphp/container
[koded-container-status]: https://img.shields.io/packagist/v/koded/container.svg
[koded-container-package]: https://packagist.org/packages/koded/container

[koded-http]: https://github.com/kodedphp/http
[koded-http-status]: https://img.shields.io/packagist/v/koded/http.svg
[koded-http-package]: https://packagist.org/packages/koded/http

[cache-simple]: https://github.com/kodedphp/cache-simple
[cache-simple-status]: https://img.shields.io/packagist/v/koded/cache-simple.svg
[cache-simple-package]: https://packagist.org/packages/koded/simple-cache

[koded-session]: https://github.com/kodedphp/session
[koded-session-status]: https://img.shields.io/packagist/v/koded/session.svg
[koded-session-package]: https://packagist.org/packages/koded/session

[koded-logging]: https://github.com/kodedphp/logging
[koded-logging-status]: https://img.shields.io/packagist/v/koded/logging.svg
[koded-logging-package]: https://packagist.org/packages/koded/logging

[koded-i18n]: https://github.com/kodedphp/i18n
[koded-i18n-status]: https://img.shields.io/packagist/v/koded/i18n.svg
[koded-i18n-package]: https://packagist.org/packages/koded/i18n

