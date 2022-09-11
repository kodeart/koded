<?php

namespace Tests\Koded\Framework\Middleware;

use Koded\Framework\App;
use Koded\Framework\Middleware\CorsMiddleware;
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Tests\Koded\Framework\Fixtures\TestResource;

class CorsMiddlewareWithoutConfigurationTest extends TestCase
{
    private ?App $app;

    public function test_get_method_without_credentials()
    {
        unset($_SERVER['HTTP_COOKIE']);
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        $this->assertSame(
            '*',
            $response->getHeaderLine('Access-Control-Allow-Origin'),
            '(simple CORS request) Allow-Origin is set to "*" for GET method
            because there is no request cookie (no credentials)');

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'),
                           'Allow-Credentials is not set');
    }


    public function test_get_method_with_credentials()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';
        $_SERVER['HTTP_COOKIE'] = 'foo=bar';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        /**
         * @var ServerRequestInterface $request
         * @var ResponseInterface $response
         */
        [$request, $response] = call_user_func($this->app);

        // Response object

        $this->assertSame(
            'http://example.org',
            $response->getHeaderLine('Access-Control-Allow-Origin'),
            'Allow-Origin header is same as the Origin header value
            because the cookie is set (credentials)');

        // Request object

        $this->assertTrue($request->hasHeader('Origin'));

        $this->assertSame(['DELETE', 'POST', 'GET', 'HEAD', 'OPTIONS'],
            $request->getAttribute('@http_methods'));
    }


    public function test_preflight_request_with_credentials()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';
        $_SERVER['HTTP_COOKIE'] = 'foo=bar';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'POST';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        // Response object

        $this->assertSame(
            'http://example.org',
            $response->getHeaderLine('Access-Control-Allow-Origin'),
            'Allow-Origin header is same as Origin in the request'
        );
        $this->assertSame(
            'true',
            $response->getHeaderLine('Access-Control-Allow-Credentials'),
            'Allow-Credentials is set because of the request cookie'
        );

        $this->assertSame(
            'DELETE,POST,GET,HEAD,OPTIONS',
            $response->getHeaderLine('Access-Control-Allow-Methods'),
            'Allowed-Methods header is added'
        );

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Headers'),
                           'Allow-Headers is not set, because Request-Headers is not set in the request');

        $this->assertSame(
            'Authorization, X-Forwarded-With',
            $response->getHeaderLine('Access-Control-Expose-Headers'),
            'Default exposed headers from configuration are set in the response headers'
        );

        $this->assertFalse(
            $response->hasHeader('Allow'),
            'Allow header is removed'
        );

        $this->assertSame(
            HttpStatus::NO_CONTENT,
            $response->getStatusCode()
        );

        $this->assertSame(
            'text/plain',
            $response->getHeaderLine('Content-type')
        );
    }

    /**
     * @depends test_preflight_request_with_credentials
     */
    public function test_options_method_for_not_allowed_method()
    {
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';
        $_SERVER['HTTP_COOKIE'] = 'foo=bar';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'PUT';

        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';

        /** @var ResponseInterface $response */
        [, $response] = call_user_func($this->app);

        // Response object

        $this->assertSame(
            HttpStatus::NO_CONTENT,
            $response->getStatusCode()
        );

        $this->assertSame(
            'text/plain',
            $response->getHeaderLine('Content-type')
        );

        $this->assertSame(
            'DELETE,POST,GET,HEAD,OPTIONS',
            $response->getHeaderLine('Access-Control-Allow-Methods'),
            'Allowed-Methods header is added no matter the Request-Method from the request'
        );
    }

    /**
     * @depends test_options_method_for_not_allowed_method
     */
    public function test_preflight_with_allow_headers()
    {
        $this->markTestSkipped('wip...');

        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';
        $_SERVER['HTTP_COOKIE'] = 'foo=bar';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'PUT';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] = 'Accept, Authorization, Content-type, X-Api-Key';

        [, $response] = call_user_func($this->app);

        $this->assertSame(
            'Authorization,X-Api-Key',
            $response->getHeaderLine('Access-Control-Allow-Headers'),
            'Simple headers are filtered out'
        );
    }

    protected function setUp(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $this->app = (new App(
            renderer: [$this, '_renderer']
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
