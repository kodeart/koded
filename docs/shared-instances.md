# Shared instances

| spec   | interface                        | default implementation
|-------:|:---------------------------------|:----------------------
| PSR-3  | `Psr\Log\LoggerInterface`        | `Koded\Logging\Log`
| PSR-16 | `Psr\SimpleCache\CacheInterface` | `Koded\Caching\Client\MemoryClient`
|        | `Koded\Stdlib\Configuration`     | `Koded\Stdlib\Config`
|        | N/A                              | `Koded\Framework\Router`

[comment]: <> (| &#40;TODO&#41; | `Koded\Serializer\Serializer`    | `Koded\Serializer\ObjectSerializer`)


# Default bindings

| spec   | interface                                 | default implementation
|-------:|:------------------------------------------|:----------------------
| PSR-7  | `Psr\Http\Message\ServerRequestInterface` | `Koded\Http\ServerRequest`
| PSR-7  | `Psr\Http\Message\ResponseInterface`      | `Koded\Http\ServerResponse`
| PSR-7  | `Koded\Http\Interfaces\Request`           | `Koded\Http\ServerRequest`
| PSR-7  | `Koded\Http\Interfaces\Response`          | `Koded\Http\ServerResponse`
|        | `Koded\Framework\Auth\AuthBackend`        | `Koded\Framework\Auth\SessionAuthBackend`
|        | `Koded\Framework\Auth\AuthProcessor`      | `Koded\Framework\Auth\TokenAuthProcessor`

