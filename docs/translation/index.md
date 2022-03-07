I18n setup
==========

**The world is multilingual** and Koded provides a simple I18n implementation.

Translation strings are stored on a disk, processed by 
a "catalog" and accessed with translation function `__()`.

```php
<?php

function __(
  string $string,
  array $arguments = [],
  string $locale = ''
): string
```

Configuration
-------------

I18n is set in the Koded `DIModule` and defaults to

  - [`DefaultCatalog`](catalogs.md#defaultcatalog) and
  - [`DefaultFormatter`](catalogs.md#defaultformatter)

Both can be changed in your app configuration:

```php
<?php

return [
  'translation.catalog' => \Koded\I18n\GettextCatalog::class,
  'translation.formatter' => \Koded\I18n\StrtrFormatter::class,
  'translation.dir' => __DIR__ . '/locale/',
  'translation.locale' => 'mk_MK',
];
```


*[I18n]: Internationalization