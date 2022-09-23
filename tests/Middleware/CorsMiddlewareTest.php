<?php

namespace Tests\Koded\Framework\Middleware;

use Koded\Framework\App;
use Koded\Framework\Middleware\CorsMiddleware;
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Tests\Koded\Framework\Fixtures\TestResource;

class CorsMiddlewareTest extends TestCase
{
    private ?App $app;

    public function test_without_origin_header()
    {
        /** @var ResponseInterface $response */
        [$request, $response] = call_user_func($this->app);

        $this->assertSame(HttpStatus::OK,
                          $response->getStatusCode(),
                          'CORS skipped');

        $this->assertFalse($request->hasHeader('Origin'),
                          'CORS skipped, Origin header is not set in the request object');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'),
                          'CORS skipped, Origin header is not set in the response');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'),
                           'CORS skipped, credentials header is not set in the response');
    }

    public function test_with_same_host_and_origin()
    {
        $_SERVER['HTTP_COOKIE'] = 'foo=bar';
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'),
            'CORS skipped, Origin and host are the same');
    }

    public function test_credentials_with_origin_header()
    {
        $_SERVER['HTTP_COOKIE'] = 'foo=bar';
        $_SERVER['HTTP_ORIGIN'] = 'http://example.net';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertSame('true',
            $response->getHeaderLine('Access-Control-Allow-Credentials'),
            'Allow-Credentials is automatically added if origin is not "*"');

        $this->assertSame('http://example.net',
            $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function test_non_simple_request_method()
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['HTTP_ORIGIN'] = 'http://example.net';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertSame('http://example.net',
            $response->getHeaderLine('Access-Control-Allow-Origin'),
            'Non-simple requests has Origin header');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Methods'),
                          'Actual request does not have Allow-Methods');
    }

    protected function setUp(): void
    {
        unset($_SERVER['HTTP_COOKIE']);
        $_SERVER['REQUEST_URI'] = 'http://example.org/';

        $this->app = (new App(
            renderer: [$this, '_renderer'],
        ))->route('/', TestResource::class, [CorsMiddleware::class], true);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['CONTENT_TYPE']);
        unset($_SERVER['HTTP_COOKIE']);
        unset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
        unset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
        $this->app = null;
    }

    public function _renderer(ServerRequestInterface $request, ResponseInterface $response): array
    {
        return [$request, $response];
    }
}
