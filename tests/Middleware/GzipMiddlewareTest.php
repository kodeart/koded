<?php

namespace Tests\Koded\Framework\Middleware;

use Koded\Framework\App;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function Koded\Http\create_stream;

class GzipMiddlewareTest extends TestCase
{
    private string $payload = 'Hello Universe!';

    /**
     * @dataProvider supportedValues
     */
    public function test_with_payload_and_supported_accept_value($value)
    {
        $_SERVER['HTTP_ACCEPT_ENCODING'] = $value;

        $app = (new App(
            renderer: [$this, '_renderer']
        ))->route('/', function(ResponseInterface $response) {
            return $response->withBody(create_stream($this->payload));
        });

        [$request, $response] = call_user_func($app);

        $this->assertEquals(
            $value,
            $request->getHeaderLine('Accept-Encoding'),
            "(Request) Accept-Encoding is set to $value");

        $this->assertEquals(
            'gzip',
            $response->getHeaderLine('Content-Encoding'),
            '(Response) Content-Encoding header is set from request Accept-Encoding');

        $this->assertEquals('Accept-Encoding', $response->getHeaderLine('Vary'),
            '(Response} Vary header is set to Accept-Encoding');

        $this->assertNotSame(
            strlen($this->payload),
            $response->getBody()->getSize(),
            '(Response) The payload is encoded with gzip');
    }

    /**
     * @dataProvider unsupportedValues
     */
    public function test_with_payload_and_unsupported_request_value($value)
    {
        $_SERVER['HTTP_ACCEPT_ENCODING'] = $value;

        $app = (new App(
            renderer: [$this, '_renderer']
        ))->route('/', function(ResponseInterface $response) {
            return $response->withBody(create_stream($this->payload));
        });

        [$request, $response] = call_user_func($app);

        $this->assertEquals(
            $value,
            $request->getHeaderLine('Accept-Encoding'),
            '(Request) Accept-Encoding is set to unsupported value anyways');

        $this->assertEquals('', $response->getHeaderLine('Content-Encoding'),
            '(Response) Content-Encoding header is not set; payload is empty');

        $this->assertEquals('', $response->getHeaderLine('Vary'),
            '(Response) Vary header is not set to Accept-Encoding; payload is empty');

        $this->assertSame(
            strlen($this->payload),
            $response->getBody()->getSize(),
            '(Response) The payload is not encoded with gzip, ' .
            'because the request header value is not supported');
    }

    /**
     * @dataProvider supportedValues
     */
    public function test_without_payload($value)
    {
        $_SERVER['HTTP_ACCEPT_ENCODING'] = $value;

        $app = (new App(
            renderer: [$this, '_renderer']
        ))->route('/', function(ResponseInterface $response) {
            return $response;
        });

        [$request, $response] = call_user_func($app);

        $this->assertEquals(
            $value,
            $request->getHeaderLine('Accept-Encoding'),
            "(Request) Accept-Encoding is set to: $value");

        $this->assertEquals('', $response->getHeaderLine('Content-Encoding'),
            '(Response) Content-Encoding header is not set; payload is empty');

        $this->assertEquals('', $response->getHeaderLine('Vary'),
            '(Response) Vary header is not set to Accept-Encoding; payload is empty');

        $this->assertSame(
            0,
            $response->getBody()->getSize(),
            'The payload is not encoded with gzip, ' .
            'because there is no payload');
    }

    /**
     * @dataProvider supportedValues
     */
    public function test_with_already_set_response_header($value)
    {
        $_SERVER['HTTP_ACCEPT_ENCODING'] = $value;

        $app = (new App(
            renderer: [$this, '_renderer']
        ))->route('/', function(ResponseInterface $response) use ($value) {
            return $response->withHeader('Content-Encoding', $value);
        });

        [$request, $response] = call_user_func($app);

        $this->assertEquals(
            $value,
            $request->getHeaderLine('Accept-Encoding'),
            'Request Accept-Encoding is set to gzip');

        $this->assertEquals(
            $value,
            $response->getHeaderLine('Content-Encoding'),
            'Response Content-Encoding header is set from request Accept-Encoding');

        $this->assertEquals(
            '',
            $response->getHeaderLine('Vary'),
            'Response Vary header is not set to Accept-Encoding through the middleware class');

        $this->assertSame(
            0,
            $response->getBody()->getSize(),
            'The payload is encoded with gzip');
    }

    public function supportedValues()
    {
        return [
            ['gzip'],
            ['br, *'],
            ['*']
        ];
    }

    public function unsupportedValues()
    {
        return [
            ['unsupported-by-middleware'],
            ['']
        ];
    }

    public function _renderer(
        ServerRequestInterface $request,
        ResponseInterface $response)
    {
        return [$request, $response];
    }

    protected function setUp(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_ACCEPT_ENCODING']);
    }
}
