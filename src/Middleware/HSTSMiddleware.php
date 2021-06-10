<?php

namespace Koded\Framework\Middleware;

use Koded\Http\Interfaces\HttpStatus;
use Koded\Http\ServerResponse;
use Koded\Stdlib\Configuration;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class HSTSMiddleware implements MiddlewareInterface
{
    private int $maxAge = 0;
    private string $includeSubDomains = '';

    public function __construct(Configuration $settings)
    {
        $this->maxAge = (int)$settings->get('hsts.maxAge', $this->maxAge);
        if ($settings->get('hsts.includeSubdomains', $this->includeSubDomains)) {
            $this->includeSubDomains = ';includeSubDomains';
        }
    }
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ('https' !== $request->getUri()->getScheme()) {
            return (new ServerResponse(null, HttpStatus::MOVED_PERMANENTLY))
                ->withHeader('Location', (string)$request->getUri()->withScheme('https'));
        }
        return $handler->handle($request)->withHeader(
            'Strict-Transport-Security', 'max-age=' . $this->maxAge . $this->includeSubDomains
        );
    }
}
