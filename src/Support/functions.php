<?php declare(strict_types=1);

use Koded\Framework\HTTPError;
//use Koded\Framework\I18n\{I18n, I18nCatalog};
use Koded\Http\AcceptHeaderNegotiator;
use Koded\Http\Interfaces\{Request, Response};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use function Koded\Http\create_stream;


function start_response(Request $request, Response $response): void
{
    if (false === $response->hasHeader('Content-Type')) {
        $media = $request->getAttribute('@media') ?? client_prefers($request);
        // [NOTE]: Web browsers have weird Accept headers and
        // this renderer overrules the default XML and/or XHTML type
        // by preferring JSON, hence forcing the response for ReST apps.
        if (str_contains($media, 'html')) {
            $media = 'application/json';
        }
        $response = $response
            ->withHeader('Content-Type', $media)
            ->withAddedHeader('Vary', 'Content-Type');
    }
    $response->getBody()->rewind();
    $response->sendHeaders();
    echo $response->sendBody();
}

/**
 * Content type negotiation.
 * Finds the closest match for Accept request header.
 *
 * @param ServerRequestInterface $request
 * @return string The content type that matches the Accept header.
 *                If catch-all is provided, defaults to application/json
 */
function client_prefers(ServerRequestInterface $request): string
{
    $media = (new AcceptHeaderNegotiator('*/*'))
        ->match($request->getHeaderLine('Accept') ?: '*/*')
        ->value();

    if ('*' === $media) {
        return 'application/json';
    }
    return $media . (str_contains($media, 'json') ? '' : '; charset=UTF-8');
}

/**
 * Exceptions serializer (follows the RFC-7807).
 *
 * @param ServerRequestInterface $request
 * @param ResponseInterface      $response
 * @param HTTPError              $exception
 * @return ResponseInterface
 * @see https://tools.ietf.org/html/rfc7807
 */
function default_serialize_error(
    ServerRequestInterface $request,
    ResponseInterface $response,
    HTTPError $exception): ResponseInterface
{
    $exception->setInstance($request->getUri()->getPath());
    $response = $response
        ->withHeader('X-Error-Status', join(' ', [$response->getStatusCode(), $response->getReasonPhrase()]))
        ->withHeader('X-Error-Message', str_replace(["\n", "\r", "\t"], ' ', $exception->getDetail()))
        ->withHeader('Cache-Control', 'no-cache, max-age=0')
        ->withHeader('Connection', 'close');

    if (str_contains(client_prefers($request), 'xml')) {
        return $response
            ->withHeader('Content-Type', 'application/problem+xml')
            ->withBody(create_stream($exception->toXml()));
    }
    return $response
        ->withHeader('Content-Type', 'application/problem+json')
        ->withBody(create_stream($exception->toJson()));
}
