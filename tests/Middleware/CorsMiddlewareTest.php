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

    public function test_simple_method()
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

    public function test_when_origin_equals_request_uri()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';
        $_SERVER['REQUEST_URI'] = 'http://example.org/';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertSame(HttpStatus::OK,
                          $response->getStatusCode(),
                          'CORS skipped');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'),
                           'CORS skipped, Origin header is not set in the response');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'),
                           'CORS skipped, credentials header is not set in the response');
    }

    public function test_non_simple_method()
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertSame(HttpStatus::OK,
                          $response->getStatusCode(),
                          'CORS skipped');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'),
                           'CORS skipped, Origin header is not set in the response');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'),
                           'CORS skipped, credentials header is not set in the response');
    }

    public function test_simple_method_with_content_type()
    {
        $_SERVER['HTTP_ORIGIN'] = '/';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertSame('*',
                          $response->getHeaderLine('Access-Control-Allow-Origin'),
                          'CORS middleware skips processing and sets Origin to asterisk (*)');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'),
                           'CORS credentials header is not set in the response');

        $this->assertSame('Origin',
                          $response->getHeaderLine('Vary'));
    }

    protected function setUp(): void
    {
        unset($_SERVER['HTTP_COOKIE']);
        $_SERVER['REQUEST_URI'] = '/';

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
        $this->app = null;
    }

    public function _renderer(ServerRequestInterface $request, ResponseInterface $response): array
    {
        return [$request, $response];
    }
}
