URI parameters
==============

The URI parameters are **named variables** wrapped with 
curly braces `{}` in the **URI template** string.

```php
  ->route('/{param1}/{param2}/{param3}/...')
```

If the URI matches the route template, the values of the parameters 
will be stored in the `Psr\Http\Message\ServerRequestInterface@attributes` 
and available through the request object with the `getAttribute()` method

```php
<?php

  public function put(ServerRequestInterface $request) {
    $param1 = $request->getAttribute('param1');
    $param2 = $request->getAttribute('param2');
    $param3 = $request->getAttribute('param3');
    ...
  }
```

Parameter types
---------------

The router supports simple types for automatic value typecasts.

!!! info "The values are strings by default"
    - str
    - int
    - float
    - UUID
    - path
    - regex

### str

```php
  ->route('/{id}') or
  ->route('/{id:str}')

  // ie. "/123" 
  // the value is STRING ('id' => '123')
```


### int

```php
  ->route('/{id:int}')

  // ie. "/123" 
  // the value is INTEGER ('id' => 123)
```

### float

```php
  ->route('/{lon:float}/{lat:float}')

  // ie. "/41.9973/21.4325" 
  // the values are FLOATS ('lon' => 41.9973, 'lat' => 21.4325)
```

### UUID

```php
  ->route('/{ident:UUID}')

  // ie. "/7eacf466-321f-4ceb-914e-e525987e7804" 
  // the value is STRING ('ident' => '7eacf466-321f-4ceb-914e-e525987e7804')
```

### path

```php
  ->route('/collection/{dir:path}')

  // ie. "/collection/deeper/subgroup/name" 
  // the value is STRING ('dir' => 'deeper/subgroup/name')
```

!!! warning "Only one `:path` parameter is supported"

### regex

```php
  ->route('/{something:regex:\d+}')

  // ie. "/123" 
  // 'something' => 123
```

!!! info "Keep it simple"
    Try to not overcomplicate your regular expressions,
    in most cases a simple type is sufficient.
