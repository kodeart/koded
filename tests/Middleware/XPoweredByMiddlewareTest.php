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

    public function test_xpoweredby_with_null_value()
    {
        $this->app
            ->route('/', TestResource::class, [
                XPoweredByMiddleware::class
            ], true);

        [, $response] = call_user_func($this->app);

        $this->assertArrayNotHasKey('x-powered-by', $response->getHeaders());
        $this->assertSame('', $response->getheaderLine('X-Powered-By'));
        $this->assertSame([], $response->getHeader('x-powered-by'));
    }

    public function test_xpoweredby_value()
    {
        $this->app
            ->route('/', TestResource::class, [
                new XPoweredByMiddleware('koded')
            ], true);

        [, $response] = call_user_func($this->app);

        $this->assertSame(
            'koded',
            $response->getheaderLine('X-Powered-By'),
            'Should set the value in the header'
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
        ));
    }
}
