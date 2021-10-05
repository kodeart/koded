# Default framework dependencies

## Framework DI module

It loads the following dependencies by default:

### Interfaces
See [implementations](implementations) and [shared instances](shared-instances.md).

### HTTP

- PSR-7 `Psr\Http\Message\ServerRequestInterface`
- PSR-7 `Psr\Http\Message\ResponseInterface`
- `Koded\Framework\Request`
- `Koded\Framework\Response`

### Authentication

- `Framework\Auth\AuthBackend`

    Used in the _AuthMiddleware_ 
- `Framework\Auth\AuthProcessor`

    Executes the `authenticate()` in the _AuthMiddleware_
    
> Application should override the `AuthBackend` to provide
> its own authentication storage and logic (i.e. database).

>  The `AuthProcessor` parses the credentials and may be
> overridden to support different types of auth mechanisms
> (i.e. JWT)

### Utility

- PSR-16 `Psr\SimpleCache\CacheInterface`
- PSR-3 `Psr\Log\LoggerInterface`
- `Koded\Stdlib\Configuration`

[comment]: <> (- `Koded\Serializer\Serializer`)

### Middleware

Koded uses PSR-15 middlewares. More details on the [middleware page](middleware/psr-15.md)

### Bindings

Anything that's bound by the default DI module can be replaced
with custom implementation(s) in your application DI module(s).
The default classes are from the [Koded libraries](//github.com/kodedphp).
