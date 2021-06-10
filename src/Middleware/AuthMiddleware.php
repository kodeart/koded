<?php

namespace Koded\Framework\Middleware;

use Koded\Framework\Auth\{AuthBackend, AuthProcessor};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class AuthMiddleware implements MiddlewareInterface
{
    private AuthProcessor $processor;
    private AuthBackend $backend;

    public function __construct(AuthProcessor $processor, AuthBackend $backend)
    {
        $this->processor = $processor;
        $this->backend = $backend;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
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
