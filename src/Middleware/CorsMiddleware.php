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

    private readonly bool $disabled;
    private readonly string $origin;
    private readonly string $methods;
    private readonly string $headers;
    private readonly string $expose;
    private readonly int $maxAge;

    /**
     * The configuration is used to force/override the CORS header values.
     * By default, most of them are not predefined or have a commonly used values.
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
        $this->disabled = (bool)$config->get('cors.disable');
        $this->origin = trim($config->get('cors.origin')) ?: '*';
        $this->methods = strtoupper(trim($config->get('cors.methods'))) ?: '*';
        $this->headers = trim($config->get('cors.headers')) ?: '*';
        $this->expose = trim($config->get('cors.expose')) ?: '*';
        $this->maxAge = (int)$config->get('cors.maxAge');
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (false === $request->hasHeader('Origin')) {
            return $response;
        }
        if ($this->isPreFlightRequest($request)) {
            return $this->responseForPreFlightRequest($request, $response);
        }
        if ($this->isSimpleRequest($request)) {
            return $this->responseForSimpleRequest($request, $response);
        }
        return $this->responseForActualRequest($request, $response);
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
        if ($this->disabled) {
            // https://fetch.spec.whatwg.org/#http-responses
            return $response->withStatus(HttpStatus::FORBIDDEN);
        }
        $withCredentials = $request->hasHeader('Cookie');
        if ($origin = $this->getOrigin($request, $withCredentials)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }
        if ($withCredentials || '*' !== $origin) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        return $response->withAddedHeader('Vary', 'Origin');
    }

    private function responseForActualRequest(
        ServerRequestInterface $request,
        ResponseInterface $response) : ResponseInterface
    {
        $response = $this->responseForSimpleRequest($request, $response);
        if ($expose = $this->getExposedHeaders($request->hasHeader('Cookie'))) {
            $response = $response->withHeader('Access-Control-Expose-Headers', $expose);
        }
        return $response;
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
        if ($this->disabled) {
            // https://fetch.spec.whatwg.org/#http-responses
            return $response->withStatus(HttpStatus::FORBIDDEN);
        }
        $response = $this->responseForSimpleRequest($request, $response);
        $withCredentials = $request->hasHeader('Cookie');
        $response = $response->withHeader(
            'Access-Control-Allow-Methods',
            $this->getAllowedMethods($request, $withCredentials)
        );
        if ($headers = $this->getPreflightAllowedHeaders($request)) {
            $response = $response->withHeader('Access-Control-Allow-Headers', $headers);
        }
        if ($expose = $this->getExposedHeaders($withCredentials)) {
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
        bool $withCredentials): string
    {
        $origin = $this->origin ?: '*';
        if ($withCredentials && str_contains($origin, '*')) {
            return $request->getHeaderLine('Origin');
        }
        return $origin;
    }

    private function getAllowedMethods(
        ServerRequestInterface $request,
        bool $withCredentials): string
    {
        $methods = match (true) {
            !empty($this->methods) && !str_contains('*', $this->methods) => $this->methods,
            !empty($allowed = $request->getAttribute('@http_methods')) => join(',', $allowed),
            default => 'HEAD,OPTIONS',
        };
        if ($withCredentials && str_contains($methods, '*')) {
            return 'GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS';
        }
        return $methods;
    }

    private function getPreflightAllowedHeaders(ServerRequestInterface $request): string
    {
        if ($this->headers && !str_contains($this->headers, '*')) {
            return $this->headers;
        }
        return $request->getHeaderLine('Access-Control-Request-Headers');
    }

    private function getAllowedHeaders(
        ServerRequestInterface $request,
        bool $withCredentials): string
    {
        $headers = $this->headers ?: $request->getHeaderLine('Access-Control-Request-Headers');
        if (str_contains($headers, '*')) {
            // Return here and let the client process the consequences of
            // the forced headers from configuration, or request headers
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

    private function getExposedHeaders(bool $withCredentials): string
    {
        return ($withCredentials && str_contains($this->expose, '*'))
            ? '' : $this->expose;
    }
}
