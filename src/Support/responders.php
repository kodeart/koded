<?php declare(strict_types=1);

use Koded\Http\{HTTPBadRequest, HttpFactory, HTTPMethodNotAllowed, HTTPNotFound, HTTPNotImplemented, ServerResponse};
use Koded\Http\Client\ClientFactory;
use Koded\Http\Interfaces\{ClientType, HttpStatus};
use Psr\Http\Message\ResponseInterface;
use function Koded\Http\create_stream;


function path_not_found(string $path): callable
{
    throw new HTTPNotFound(instance: $path);
}


function bad_request(...$args): callable
{
    throw new HTTPBadRequest(...$args);
}


function method_not_allowed(array $allowed): callable
{
    throw new HTTPMethodNotAllowed($allowed);
}


function no_app_routes(): callable
{
    throw (new HTTPNotImplemented(
        title: 'No Routes',
        detail: 'No routes are defined in your application',
        type: 'https://kodeart.github.io/koded/routing/'
    ))
        ->setMember('framework', 'Koded Framework')
        ->setMember('version', get_version());
}

/**
 * Creates a responder for HTTP HEAD method.
 *
 * @param string $uri
 * @param array $methods
 * @return callable
 */
function head_response(string $uri, array $methods): callable
{
    return function () use ($uri, $methods): ResponseInterface {
        empty($methods) and $methods = ['HEAD', 'OPTIONS'];
        if (false === in_array('GET', $methods)) {
            return (new ServerResponse)->withHeader('Allow', $methods);
        }
        $get = (new ClientFactory(
            function_exists('curl_init')
                ? ClientType::CURL
                : ClientType::PHP
        ))
            ->get($uri, ['Connection' => 'close'])
            ->timeout(5)
            ->read()
            ->withoutHeader('Set-Cookie')
            ->withHeader('Allow', join(',', $methods));

        if ($get->getStatusCode() < HttpStatus::BAD_REQUEST) {
            return $get;
        }
        // If GET request fails, it returns the Allow header
        // with the failure reason in the "X-Error-*" headers
        error_log($get->getBody()->getContents());
        return $get
            ->withHeader('X-Error-Status', join(' ', [$get->getStatusCode(), $get->getReasonPhrase()]))
            ->withHeader('X-Error-Message', str_replace(["\n", "\r", "\t"], ' ', $get->getBody()))
            ->withStatus(HttpStatus::OK)
            ->withBody(create_stream(''));
    };
}

/**
 * Creates a responder for HTTP OPTIONS method.
 * This method does not return the Origin header, because it may
 * be a CORS request which should be handled by the middleware.
 *
 * @param array $methods Supported HTTP methods for the URI in question
 * @return callable The responder for the OPTIONS method
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS
 */
function create_options_response(array $methods): callable
{
    return fn(): ResponseInterface => (new HttpFactory)
        ->createResponse(HttpStatus::NO_CONTENT)
        ->withHeader('Cache-Control', 'no-cache, max-age=0')
        ->withHeader('Allow', join(',', $methods))
        ->withHeader('Content-Type', 'text/plain');
}

/**
 * Maps the HTTP methods to responder (public) methods.
 *
 * @param callable|object|string $resource
 * @return array|callable[]|object[]|string[]
 */
function map_http_methods(callable|object|string $resource): array
{
    $map = [
        'HEAD' => 'head',
        'OPTIONS' => 'options'
    ];
    foreach ([
                 'GET',
                 'POST',
                 'PUT',
                 'PATCH',
                 'DELETE'
             ] as $method) {
        if (method_exists($resource, $method)) {
            $map = [$method => strtolower($method)] + $map;
        } elseif (is_callable($resource)) {
            $map = [$method => $resource] + $map;
        }
    }
    return $map;
}
