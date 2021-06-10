<?php declare(strict_types=1);

namespace Koded\Framework\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

/**
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
 */
class NoCacheMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)
            ->withHeader('Cache-Control', 'no-cache, max-age=0');
    }
}
