<?php

namespace Koded\Framework\Middleware;

use Koded\Http\Interfaces\{HttpStatus, Request};
use Koded\Http\ServerResponse;
use Koded\Stdlib\Configuration;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use function join;
use function str_contains;
use function strtoupper;
use function trim;

/**
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 * @link https://fetch.spec.whatwg.org/#cors-protocol-and-credentials
 */
class CorsMiddleware implements MiddlewareInterface
{
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
        $this->origin = trim($config->get('cors.origin'));
        $this->methods = strtoupper(trim($config->get('cors.methods'))) ?: '*';
        $this->headers = trim($config->get('cors.headers')) ?: '*';
        $this->expose = trim($config->get('cors.expose')) ?: '*';
        $this->maxAge = (int)$config->get('cors.maxAge');
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);
        if ($this->skipProcess($request)) {
            return $response;
        }
        if ($this->isPreFlightRequest($request)) {
            return $this->responseForPreFlightRequest($request);
        }
        return $this->responseForActualRequest($request, $response);
    }

    private function isPreFlightRequest(ServerRequestInterface $request): bool
    {
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS#preflighted_requests
        return Request::OPTIONS === $request->getMethod()
            && $request->hasHeader('Access-Control-Request-Method');
    }

    /**
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS#preflighted_requests_in_cors
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    private function responseForPreFlightRequest(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->disabled) {
            // https://fetch.spec.whatwg.org/#http-responses
            return new ServerResponse('CORS is disabled', HttpStatus::FORBIDDEN);
        }
        $response = $this
            ->addOriginToResponse($request, new ServerResponse('', HttpStatus::NO_CONTENT))
            ->withHeader('Access-Control-Allow-Methods', $this->getAllowedMethods($request))
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Content-Type', 'text/plain')
            ->withAddedHeader('Vary', 'Origin')
            ->withoutHeader('Cache-Control')
            ->withoutHeader('Allow');

        if ($headers = $this->getAllowedHeaders($request)) {
            $response = $response->withHeader('Access-Control-Allow-Headers', $headers);
        }
        if ($expose = $this->getExposedHeaders($request)) {
            $response = $response->withHeader('Access-Control-Expose-Headers', $expose);
        }
        if ($this->maxAge > 0) {
            $response = $response->withHeader('Access-Control-Max-Age', (string)$this->maxAge);
        }
        return $response;
    }

    private function addOriginToResponse(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        if ($this->disabled) {
            // https://fetch.spec.whatwg.org/#http-responses
            return $response->withStatus(HttpStatus::FORBIDDEN);
        }
        $origin = $this->origin ?: $request->getHeaderLine('Origin') ?: '*';
        if ($request->hasHeader('Cookie')) {// || false === str_contains($origin, '*')) {
            $response = $response
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withAddedHeader('Vary', 'Origin');
        }
        return $response->withHeader('Access-Control-Allow-Origin', $origin);
    }

    private function responseForActualRequest(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        if ($expose = $this->getExposedHeaders($request)) {
            $response = $response->withHeader('Access-Control-Expose-Headers', $expose);
        }
        return $this->addOriginToResponse($request, $response);
    }

    private function getAllowedMethods(ServerRequestInterface $request): string
    {
        return match (true) {
            !empty($this->methods) && !str_contains('*', $this->methods) => $this->methods,
            !empty($allowed = $request->getAttribute('@http_methods')) => join(',', $allowed),
            default => $request->getHeaderLine('Access-Control-Request-Method') ?: 'HEAD,OPTIONS'
        };
    }

    private function getAllowedHeaders(ServerRequestInterface $request): string
    {
        return ($this->headers && false === str_contains($this->headers, '*'))
            ? $this->headers
            : $request->getHeaderLine('Access-Control-Request-Headers');
    }

    private function getExposedHeaders(ServerRequestInterface $request): string
    {
        return ($this->expose && false === str_contains($this->expose, '*'))
            ? $this->expose
            : '';
    }

    private function skipProcess(ServerRequestInterface $request): bool
    {
        if (false === $request->hasHeader('Origin')) {
            return true;
        }
        // Same origin?
        return $request->getHeaderLine('Origin') === $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();
    }
}
