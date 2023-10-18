<?php

namespace Tests\Koded\Framework;

use Exception;
use Koded\Framework\App;
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ErrorHandlersTest extends TestCase
{
    public function test_default_exception_handler()
    {
        $_SERVER['HTTP_X_REQUEST_TEST_HEADER'] = 'x request test';

        /**
         * @var ServerRequestInterface $request
         * @var ResponseInterface $response
         */

        $app = new App(renderer: [$this, '_renderer']);
        $app->route('/', fn() => throw new Exception('boooom', 400));
        [$request, $response] = call_user_func($app);

        // Request object
        $this->assertTrue($request->hasHeader('x-request-test-header'), 'Request object state is preserved');
        $this->assertSame('x request test', $request->getHeaderLine('x-request-test-header'));

        $this->assertTrue($request->hasHeader('request-test-header'));
        $this->assertSame('request test header', $request->getHeaderLine('request-test-header'));

        // Response object
        $this->assertTrue($response->hasHeader('response-test-header'));
        $this->assertSame('response test header', $response->getHeaderLine('response-test-header'));

        $this->assertJsonStringEqualsJsonString(
            <<<'JSON'
            {
                "status":400,
                "instance":"/",
                "detail":"boooom",
                "title":"Exception",
                "type":"https://httpstatuses.com/400"
                }
            JSON,
            $response->getBody()->getContents(),
            'The exception is captured in the response payload (JSON by default)'
        );

        $this->assertSame(HttpStatus::BAD_REQUEST, $response->getStatusCode(),
                          'Response status is same as the exception code (defaults to 409 if not provided)');

        $this->assertTrue($response->hasHeader('response-test-header'),
                          'Response object state is modified in the renderer');

        $this->assertFalse($response->hasHeader('x-powered-by'),
                           'The middleware is skipped for exceptions');
    }

    public function test_without_exception_handler()
    {
        $exceptionMessage = 'boooom';

        $this->expectException(Exception::class);
        $this->expectExceptionCode(HttpStatus::BAD_REQUEST);
        $this->expectExceptionMessage($exceptionMessage);

        $app = new App(renderer: [$this, '_renderer']);
        $app->withoutErrorHandler(Exception::class);
        $app->route('/', fn() => throw new Exception($exceptionMessage, 400));
        call_user_func($app);
    }

    public function test_throwable_for_route_method()
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionCode(1);
        $this->expectExceptionMessage('URI template has duplicate slashes');

        (new App)
            ->route('//', fn(ResponseInterface $r) => $r);

        $this->assertJsonStringEqualsJsonString(
            <<<'JSON'
            {
                "status":409,
                "instance":"/",
                "detail":"URI template has duplicate slashes",
                "title":"Conflict",
                "type":"https://httpstatuses.com/409"
                }
            JSON,
            $this->getActualOutput(),
            'The exception is captured in the response payload (JSON by default)'
        );
    }

    protected function setUp(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        ini_set('error_log', '/dev/null');
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_REQUEST_TEST_HEADER']);
        ini_set('error_log', '');
    }

    public function _renderer(ServerRequestInterface $request, ResponseInterface $response): array
    {
        $request = $request->withHeader('request-test-header', 'request test header');
        $response = $response->withHeader('response-test-header', 'response test header');
        return [$request, $response];
    }
}
