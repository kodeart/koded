<?php

namespace Tests\Koded\Framework\Middleware;

use Koded\Framework\App;
use Koded\Framework\Middleware\XPoweredByMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Koded\Framework\Fixtures\TestResource;

class XPoweredByMiddlewareTest extends TestCase
{
    private App $app;

    public function test_xpoweredby_headers()
    {
        $version = file_get_contents(__DIR__ . '/../../VERSION');
        [, $response] = call_user_func($this->app);

        $this->assertStringContainsString(
            $version,
            $response->getheaderLine('X-Powered-By'),
            'Should set the version in the header'
        );
    }

    public function test_xpoweredby_version()
    {
        define('VERSION', ['1.2.3', 'dev', '0']);
        [, $response] = call_user_func($this->app);

        $this->assertSame(
            'Koded v1.2.3-dev',
            $response->getHeaderLine('x-powered-by')
        );
    }

    public function _renderer(
        ServerRequestInterface $request,
        ResponseInterface $response): array
    {
        return [$request, $response];
    }

    protected function setUp(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $this->app = (new App(
            renderer: [$this, '_renderer']
        ))->route('/', TestResource::class, [XPoweredByMiddleware::class], true);
    }
}
