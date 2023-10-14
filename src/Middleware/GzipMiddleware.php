<?php

namespace Koded\Framework\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use function gzencode;
use function Koded\Http\create_stream;
use function str_contains;

class GzipMiddleware implements MiddlewareInterface
{
    private const ACCEPT_ENCODING = 'Accept-Encoding';
    private const CONTENT_ENCODING = 'Content-Encoding';

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response->hasHeader(self::CONTENT_ENCODING)) {
            // Probably already encoded; move on
            return $response;
        }
        if (empty($response->getBody()->getSize())) {
            return $response;
        }
        if (false === $this->isAcceptable($request->getHeaderLine(self::ACCEPT_ENCODING))) {
            return $response;
        }
        $response->getBody()->rewind();
        return $response
            ->withHeader(self::CONTENT_ENCODING, 'gzip')
            ->withAddedHeader('Vary', self::ACCEPT_ENCODING)
            ->withBody(create_stream(
                gzencode($response->getBody()->getContents(), 7),
                (string)$response->getBody()->getMetadata('mode')
            ));
    }

    private function isAcceptable(string $encoding): bool
    {
        return match (true) {
            // NOTE: most common first
            empty($encoding) => false,
            str_contains($encoding, 'gzip'),
            str_contains($encoding, '*') => true,
            default => false
        };
    }
}
