<?php

namespace Koded\Framework\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class XPoweredByMiddleware implements MiddlewareInterface
{
    private readonly string|null $value;

    public function __construct(string|null $value = null)
    {
        $this->value = empty($value) ? 'Koded v' . get_version() : $value;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)
            ->withHeader('X-Powered-By', $this->value);
    }
}
