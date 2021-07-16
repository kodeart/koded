<?php

namespace Tests\Koded\Framework;

use Koded\Framework\App;
use Koded\Framework\Middleware\XPoweredByMiddleware;
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ErrorHandlersTest extends TestCase
{
    public function test_default_exception_handler()
    {
        ini_set('error_log', '/dev/null');

        /**
         * @var ServerRequestInterface $request
         * @var ResponseInterface $response
         */

        $app = new App(
            renderer: [$this, '_renderer'],
            middleware: [XPoweredByMiddleware::class]
        );

        $app->route('/', fn() => throw new \Exception('boooom', 400));

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

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage($exceptionMessage);

        $app = new App(
            renderer: [$this, '_renderer'],
            middleware: [XPoweredByMiddleware::class]
        );

        $app->withoutErrorHandler(\Exception::class);

        $app->route('/', fn() => throw new \Exception($exceptionMessage, 400));
        call_user_func($app);
    }

    protected function setUp(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_X_REQUEST_TEST_HEADER'] = 'x request test';
    }

    public function _renderer(ServerRequestInterface $request, ResponseInterface $response): array
    {
        $request = $request->withHeader('request-test-header', 'request test header');
        $response = $response->withHeader('response-test-header', 'response test header');
        return [$request, $response];
    }
}
