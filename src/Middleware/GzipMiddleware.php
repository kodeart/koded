<?php

namespace Koded\Framework\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use function Koded\Http\create_stream;

class GzipMiddleware implements MiddlewareInterface
{
    private const ACCEPT_ENCODING = 'Accept-Encoding';
    private const CONTENT_ENCODING = 'Content-Encoding';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($response->hasHeader(self::CONTENT_ENCODING)) {
            return $response;
        }
        if (!$response->getBody()->getSize()) {
            return $response;
        }
        if (false === $this->isAcceptable($request->getHeaderLine(self::ACCEPT_ENCODING))) {
            return $response;
        }
        $response->getBody()->rewind();
        return $response
            ->withHeader(self::CONTENT_ENCODING, 'gzip')
            ->withAddedHeader('Vary', self::CONTENT_ENCODING)
            ->withBody(create_stream(
                \gzencode($response->getBody()->getContents(), 7),
                $response->getBody()->getMetadata('mode')
            ));
    }

    private function isAcceptable(string $encoding): bool
    {
        if (empty($encoding)) {
            return false;
        }
        if (\str_contains($encoding, 'gzip')) {
            return true;
        }
        if (\str_contains($encoding, '*')) {
            return true;
        }
        return false;
    }
}
