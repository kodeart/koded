Storages for translation strings
================================

Koded offers two different catalogs out of the box

  - `GettextCatalog`
  - `DefaultCatalog`

Both are doing the same thing and the difference is how the 
translated strings are stored.

!!! info "Which one to use?"
    - `GettextCatalog` for projects with lots of languages 
    (therefore lots of translators).
    - `DefaultCatalog` for simple projects or relatively
    small amount of strings or languages support.

    There is no right or wrong choice, just pick one that
    you think is easy to work with or is suitable for your project. 


### DefaultCatalog

The strings are stored in the `/locales/` application 
directory in a `.php` file with a locale name

```
locales
├── de_DE.php
├── en_US.php
└── mk_MK.php

etc.
```

The structure for the `en_US.php` translation file is
```php
<?php

return [
    'language' => 'English',
    'messages' => [
    ]
];
```
and all locales are expected to be of the same format.

#### Examples

```php

# locales/en_US.php

<?php

return [
    'language' => 'English',
    'messages' => [
      'original string' => 'translated string',
      'pagination.pages' => 'page {0} of {1}',
    ]
];

# somewhere in your code

__('original string') // outputs: "translated string"
__('pagination.pages', [1, 42]) // outputs: "page 1 of 42"
```


### GettextCatalog

This catalog requires the `gettext` PHP extension and uses the 
excellent translation functionality provided by it.

The strings are stored in `.po/.mo` files within a proper
directory structure:

```
locales
└── en_US
    └── LC_MESSAGES
        ├── messages.mo
        └── messages.po
etc.
```
The recommended translation editor is [Poedit][poedit] that supports
this kind of translations.


[poedit]: https://poedit.net
