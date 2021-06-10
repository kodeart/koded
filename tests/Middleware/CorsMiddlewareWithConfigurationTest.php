<?php

namespace Tests\Koded\Framework\Middleware;

use Koded\Framework\App;
use Koded\Framework\Middleware\CorsMiddleware;
use Koded\Stdlib\{Config, Immutable};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Tests\Koded\Framework\Fixtures\TestResource;

class CorsMiddlewareWithConfigurationTest extends TestCase
{
    private ?App $app;

    public function test_get_method_without_credentials()
    {
        unset($_SERVER['HTTP_COOKIE']);

        /** @var ResponseInterface $response */
        [$request, $response] = call_user_func($this->app);

        $this->assertSame('http://example.org',
                          $request->getHeaderLine('Origin'),
                          'Origin is set in the request object');

        $this->assertSame('https://example.net',
                          $response->getHeaderLine('Access-Control-Allow-Origin'),
                          '(cors.origin) Overwritten by configuration');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'),
                           'Allow-Credentials is not set');
    }

    public function test_get_method_with_credentials()
    {
        $_SERVER['HTTP_COOKIE'] = 'foo=bar';

        /** @var ResponseInterface $response */
        [$request, $response] = call_user_func($this->app);

        $this->assertSame('http://example.org',
                          $request->getHeaderLine('Origin'),
                          'Origin is set in the request object');

        $this->assertSame('https://example.net',
                          $response->getHeaderLine('Access-Control-Allow-Origin'),
                          '(cors.origin) Overwritten by configuration');

        $this->assertSame('true',
                          $response->getHeaderLine('Access-Control-Allow-Credentials'),
                          'Allow-Credentials is set');
    }

    public function test_preflight_request()
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'PUT';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertSame('https://example.net',
                          $response->getHeaderLine('Access-Control-Allow-Origin'),
                          '(cors.origin) Overwritten by configuration');

        $this->assertSame('POST',
                          $response->getHeaderLine('Access-Control-Allow-Methods'),
                          '(cors.methods) Overwritten by configuration');

        $this->assertSame('X-Api-Token',
                          $response->getHeaderLine('Access-Control-Allow-Headers'),
                          '(cors.headers) Overwritten by configuration');

        $this->assertSame('Link',
                          $response->getHeaderLine('Access-Control-Expose-Headers'),
                          '(cors.expose) Overwritten by configuration');

        $this->assertSame('text/plain',
                          $response->getHeaderLine('Content-Type'));
    }


    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';

        $this->app = (new App(
            renderer: [$this, '_renderer'],
            config: new Config('', new Immutable([
                'cors.origin' => 'https://example.net',
                'cors.methods' => 'POST',
                'cors.headers' => 'Content-Type, X-Api-Token',
                'cors.expose' => 'Link']))
        ))->route('/', TestResource::class, [CorsMiddleware::class], true);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['HTTP_ORIGIN']);
        unset($_SERVER['HTTP_COOKIE']);
        $this->app = null;
    }

    public function _renderer(ServerRequestInterface $request, ResponseInterface $response): array
    {
        return [$request, $response];
    }
}
