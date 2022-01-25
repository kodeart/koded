<?php

namespace Koded\Framework\Middleware;

use Koded\Http\Interfaces\{HttpStatus, Request};
use Koded\Stdlib\Configuration;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use function in_array;
use function join;
use function preg_split;
use function str_contains;
use function strtolower;
use function strtoupper;
use function trim;

/**
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 * @link https://fetch.spec.whatwg.org/#cors-protocol-and-credentials
 */
class CorsMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = [
        Request::GET,
        Request::POST,
        Request::HEAD
    ];

    /**
     * HTTP/1.1 Server-driven negotiation headers
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Content_negotiation
     */
    private const SIMPLE_HEADERS = [
        'accept' => true,
        'accept-language' => true,
        'content-language' => true,
        'content-type' => true
    ];

    private bool $isDisabled;
    private string $origin;
    private string $methods;
    private string $headers;
    private string $expose;
    private int $maxAge;

    /**
     * The configuration is used to force/override the CORS header values.
     * By default none of them are set and have a commonly used values.
     *
     * Configuration directives:
     *
     *      cors.disable
     *      cors.origin
     *      cors.headers
     *      cors.methods
     *      cors.maxAge
     *      cors.expose
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->isDisabled = (bool)$config->get('cors.disable');
        $this->origin = trim($config->get('cors.origin'));
        $this->methods = strtoupper(trim($config->get('cors.methods')));
        $this->headers = trim($config->get('cors.headers'));
        $this->expose = trim($config->get('cors.expose'));
        $this->maxAge = (int)$config->get('cors.maxAge');
    }

    public function process(
        ServerRequestInterface|Request $request,
        RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (false === $request->hasHeader('Origin')) {
            return $response;
        }
        if ($request->getHeaderLine('Origin') === $request->getBaseUri()) {
            return $response;
        }
        if ($this->isPreFlightRequest($request)) {
            return $this->responseForPreFlightRequest($request, $response);
        }
        if ($this->isSimpleRequest($request)) {
            return $this->responseForSimpleRequest($request, $response);
        }
        return $response;
    }

    private function isSimpleRequest(ServerRequestInterface $request): bool
    {
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#simple_requests
        if (false === in_array($request->getMethod(), static::SAFE_METHODS, true)) {
            return false;
        }
        if ('' === $contentType = $request->getHeaderLine('Content-Type')) {
            return true;
        }
        $contentType = strtolower($contentType);
        return
            $contentType === 'application/x-www-form-urlencoded' ||
            $contentType === 'multipart/form-data' ||
            $contentType === 'text/plain';
    }

    private function isPreFlightRequest(ServerRequestInterface $request): bool
    {
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#preflighted_requests
        return Request::OPTIONS === $request->getMethod()
            && $request->hasHeader('Access-Control-Request-Method');
    }

    private function responseForSimpleRequest(
        ServerRequestInterface $request,
        ResponseInterface $response): ResponseInterface
    {
        if ($this->isDisabled) {
            // https://fetch.spec.whatwg.org/#http-responses
            return $response->withStatus(HttpStatus::FORBIDDEN);
        }
        $response = $response->withAddedHeader('Vary', 'Origin');
        if ($hasCredentials = $request->hasHeader('Cookie')) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        return $response->withHeader('Access-Control-Allow-Origin',
                                     $this->getOrigin($request, $hasCredentials));
    }

    /**
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS#preflighted_requests_in_cors
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @return ResponseInterface
     */
    private function responseForPreFlightRequest(
        ServerRequestInterface $request,
        ResponseInterface $response): ResponseInterface
    {
        if ($this->isDisabled) {
            // https://fetch.spec.whatwg.org/#http-responses
            return $response->withStatus(HttpStatus::FORBIDDEN);
        }
        $response = $this->responseForSimpleRequest($request, $response);
        $hasCredentials = $request->hasHeader('Cookie');
        $response = $response->withHeader('Access-Control-Allow-Methods',
                                          $this->getAllowedMethods($request, $hasCredentials));
        if ($headers = $this->getAllowedHeaders($request, $hasCredentials)) {
            $response = $response->withHeader('Access-Control-Allow-Headers', $headers);
        }
        if ($expose = $this->getExposedHeaders($hasCredentials)) {
            $response = $response->withHeader('Access-Control-Expose-Headers', $expose);
        }
        if ($this->maxAge > 0) {
            $response = $response->withHeader('Access-Control-Max-Age', (string)$this->maxAge);
        }
        return $response
            ->withStatus(HttpStatus::NO_CONTENT)
            ->withHeader('Content-Type', 'text/plain')
            ->withoutHeader('Cache-Control')
            ->withoutHeader('Allow');
    }

    private function getOrigin(
        ServerRequestInterface $request,
        bool $hasCredentials): string
    {
        $origin = $this->origin ?: '*';
        if ($hasCredentials && str_contains($origin, '*')) {
            return $request->getHeaderLine('Origin');
        }
        return $origin;
    }

    private function getAllowedMethods(
        ServerRequestInterface $request,
        bool $hasCredentials): string
    {
        $methods = match (true) {
            !empty($this->methods) => $this->methods,
            !empty($method = $request->getAttribute('@http_methods')) => join(',', $method),
            default => 'HEAD,OPTIONS',
        };
        if ($hasCredentials && str_contains($methods, '*')) {
            return 'GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS';
        }
        return $methods;
    }

    private function getAllowedHeaders(
        ServerRequestInterface $request,
        bool $hasCredentials): string
    {
        $headers = $this->headers ?: $request->getHeaderLine('Access-Control-Request-Headers');
        if ($hasCredentials && str_contains($headers, '*')) {
            // Return here and let the client process the consequences
            // of the forced headers from configuration, or sent headers
            return $headers;
        }
        $result = [];
        foreach (preg_split('/, */', $headers) as $header) {
            if (isset(self::SIMPLE_HEADERS[strtolower($header)])) {
                continue;
            }
            $result[] = $header;
        }
        return join(',', $result);
    }

    private function getExposedHeaders(bool $hasCredentials): string
    {
        return ($hasCredentials && str_contains($this->expose, '*'))
            ? '' : $this->expose;
    }
}
