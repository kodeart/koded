<?php

namespace Koded\Framework\Middleware;

use Koded\Framework\Auth\{AuthBackend, AuthProcessor};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthProcessor $processor,
        private readonly AuthBackend $backend
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler): ResponseInterface
    {
        if ('OPTIONS' === $request->getMethod()) {
            return $handler->handle($request);
        }
        $request = $request->withAttribute('@user', $this->processor->authenticate(
            $this->backend, $request->getHeaderLine('Authorization')
        ));
        return $handler->handle($request);
    }
}
