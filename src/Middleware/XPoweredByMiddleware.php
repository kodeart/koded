<?php

namespace Koded\Framework\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class XPoweredByMiddleware implements MiddlewareInterface
{
    public function __construct(private string|null $value = null)
    {
        @header_remove('x-powered-by');
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (null === $this->value) {
            return $response;
        }
        return $response->withHeader('x-powered-by', $this->value);
    }
}
