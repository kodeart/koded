<?php

namespace Tests\Koded\Framework\Middleware;

use Koded\Framework\App;
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Tests\Koded\Framework\Fixtures\TestEmptyResource;

class CorsMiddlewareRequestMethodsTest extends TestCase
{
    private ?App $app;

    public function test_without_http_methods_in_request_handler()
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['HTTP_ORIGIN'] = 'http://example.net';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'PUT';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertSame(
            HttpStatus::NO_CONTENT,
            $response->getStatusCode()
        );

        $this->assertSame(
            'HEAD,OPTIONS',
            $response->getHeaderLine('Access-Control-Allow-Methods'),
            'Allow-Methods set to default value (HEAD,OPTIONS)');
    }

    public function test_non_implemented_method()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ORIGIN'] = 'http://example.net';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertSame(
            HttpStatus::METHOD_NOT_ALLOWED,
            $response->getStatusCode(),
            'CORS middleware is not processed at all at this point'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = 'http://example.org/';

        $this->app = (new App(
            renderer: [$this, '_renderer'],
        ))->route('/', TestEmptyResource::class);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
        $this->app = null;
    }

    public function _renderer(ServerRequestInterface $request, ResponseInterface $response): array
    {
        return [$request, $response];
    }
}
