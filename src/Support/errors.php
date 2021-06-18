<?php declare(strict_types=1);

namespace Koded\Framework;

use Koded\Http\Interfaces\HttpStatus;

class HTTPNotFound extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::NOT_FOUND, ...$args);
    }
}

class HTTPBadRequest extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::BAD_REQUEST, ...$args);
    }
}

class HTTPMethodNotAllowed extends HTTPError
{
    public function __construct(array $allowed, ...$args)
    {
        $args['headers']['Allow'] = join(',', array_map('strtoupper', $allowed));
        parent::__construct(HttpStatus::METHOD_NOT_ALLOWED, ...$args);
    }
}

class HTTPConflict extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::CONFLICT, ...$args);
    }
}

class HTTPServiceNotFound extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::SERVICE_NOT_FOUND, ...$args);
    }
}

class HTTPUnsupportedMediaType extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::UNSUPPORTED_MEDIA_TYPE, ...$args);
    }
}

class HTTPUnprocessableEntity extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::UNPROCESSABLE_ENTITY, ...$args);
    }
}

class HTTPFailedDependency extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::FAILED_DEPENDENCY, ...$args);
    }
}

class HTTPUnauthorized extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::UNAUTHORIZED, ...$args);
    }
}

class HTTPForbidden extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::FORBIDDEN, ...$args);
    }
}

class HTTPNotImplemented extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::NOT_IMPLEMENTED, ...$args);
    }
}

class HTTPServerError extends HTTPError
{
    public function __construct(...$args)
    {
        parent::__construct(HttpStatus::INTERNAL_SERVER_ERROR, ...$args);
    }
}
