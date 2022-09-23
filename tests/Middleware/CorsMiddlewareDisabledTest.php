<?php

namespace Tests\Koded\Framework\Middleware;

use Koded\Framework\App;
use Koded\Framework\Middleware\CorsMiddleware;
use Koded\Stdlib\{Config, Immutable};
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Tests\Koded\Framework\Fixtures\TestResource;

class CorsMiddlewareDisabledTest extends TestCase
{
    private ?App $app;

    public function test_get_method_without_credentials()
    {
        unset($_SERVER['HTTP_COOKIE']);

        /** @var ResponseInterface $response */
        [$request, $response] = call_user_func($this->app);

        $this->assertSame(HttpStatus::FORBIDDEN,
                          $response->getStatusCode(),
                          '(cors.disable=TRUE) Forced status code to Forbidden');

        $this->assertSame('http://example.org',
                          $request->getHeaderLine('Origin'),
                          'Origin is set in the response object');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'),
                          '(cors.origin) Disabled by configuration');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'),
                           '(cors.disable=TRUE) Allow-Credentials is not set');
    }

    public function test_get_method_with_credentials()
    {
        $_SERVER['HTTP_COOKIE'] = 'foo=bar';

        /** @var ResponseInterface $response */
        [$request, $response] = call_user_func($this->app);

        $this->assertSame(HttpStatus::FORBIDDEN,
                          $response->getStatusCode(),
                          '(cors.disable=TRUE) Forced status code to Forbidden');

        $this->assertSame('http://example.org',
                          $request->getHeaderLine('Origin'),
                          'Origin is set in the response object');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'),
                           '(cors.origin) Disabled by configuration');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'),
                           '(cors.disable=TRUE) Allow-Credentials is not set');
    }

    public function test_preflight_request()
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'PUT';
        $_SERVER['HTTP_CACHE_CONTROL'] = 'no-cache';

        /** @var ResponseInterface $response */
        [$req, $response] = call_user_func($this->app);

        $this->assertSame(HttpStatus::FORBIDDEN,
                          $response->getStatusCode(),
                          '(cors.disable=TRUE) Forced status code to Forbidden');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'),
                           '(cors.disable=TRUE) cors origin disabled by configuration');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Methods'),
                           '(cors.disable=TRUE) cors methods disabled by configuration');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'),
                           '(cors.disable=TRUE) Allow-Credentials is not set');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Headers'),
                           '(cors.headers) Disabled by configuration');

        $this->assertFalse($response->hasHeader('Access-Control-Expose-Headers'),
                           '(cors.expose) Disabled by configuration');

        $this->assertSame('',
                          $response->getHeaderLine('Content-Type'),
                          '(cors.disable=TRUE) Content-Type is not set');

        $this->assertFalse($response->hasHeader('Cache-Control'));

        $this->assertFalse($response->hasHeader('Allow'));
    }


    protected function setUp(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';

        $this->app = (new App(
            renderer: [$this, '_renderer'],
            config: new Config('', new Immutable([
                'cors.disable' => true]
            ))
        ))->route('/', TestResource::class, [CorsMiddleware::class], true);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['HTTP_COOKIE']);
        $this->app = null;
    }

    public function _renderer(ServerRequestInterface $request, ResponseInterface $response): array
    {
        return [$request, $response];
    }
}
