<?php declare(strict_types=1);

namespace Koded\Framework\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class CallableMiddleware implements MiddlewareInterface
{
    /** @var callable $callback */
    private readonly $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->callback instanceof MiddlewareInterface) {
            return ($this->callback)->process($request, $handler);
        }
        return ($this->callback)($request, $handler);
    }
}
