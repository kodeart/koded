<?php

namespace Tests\Koded\Framework\Middleware;

use Koded\Framework\App;
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Tests\Koded\Framework\Fixtures\TestResource;

class CorsMiddlewareWithErrorsTest extends TestCase
{
    private ?App $app;

    public function test_non_existing_uri()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_COOKIE'] = 'foo=bar';
        $_SERVER['REQUEST_URI'] = 'http://example.org/fubar';
        $_SERVER['HTTP_ORIGIN'] = 'http://example.net';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertSame(
            HttpStatus::NOT_FOUND,
            $response->getStatusCode(),
        );

        $this->assertSame(
            'http://example.net',
            $response->getHeaderLine('Access-Control-Allow-Origin'));

        $this->assertSame(
            'true',
            $response->getHeaderLine('Access-Control-Allow-Credentials'));

        $this->assertTrue($response->hasHeader('X-Error-Status'));
        $this->assertTrue($response->hasHeader('X-Error-Message'));

        $this->assertStringContainsString(
            'Content-Type',
            $response->getHeaderLine('Vary'),
            'Content-Type is added in Vary header'
        );

        $this->assertSame(
            'application/problem+json',
            $response->getHeaderLine('Content-Type')
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = (new App(
            renderer: function(ServerRequestInterface $request, ResponseInterface $response) {
                return [$request, $response];
            },
        ))->route('/', TestResource::class);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['HTTP_COOKIE']);
        unset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
        $this->app = null;
    }
}
