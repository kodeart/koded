<?php

namespace Tests\Koded\Framework\Middleware;

use Koded\Framework\App;
use Koded\Framework\Middleware\CorsMiddleware;
use Koded\Stdlib\{Config, Immutable};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

class CorsMiddlewareMaxageTest extends TestCase
{
    public function test_with_same_host_and_origin()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'PUT';

        $app = (new App(
            config: new Config('', new Immutable(['cors.maxAge' => 120])),
            renderer: [$this, '_renderer']
        ))->route(
            '/',
            fn(ResponseInterface $response) => $response,
            [CorsMiddleware::class],
            true
        );

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($app);

        $this->assertSame(
            '120',
            $response->getHeaderLine('Access-Control-Max-Age'),
            'CORS MaxAge value is converted to string');
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
    }

    public function _renderer(
        ServerRequestInterface $request,
        ResponseInterface $response): array
    {
        return [$request, $response];
    }
}
