<?php

namespace Tests\Koded\Framework;

use Koded\Framework\App;
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class NoAppRoutesTest extends TestCase
{
    public function test_no_responder()
    {
        $app = new App(renderer: [$this, '_renderer']);

        /** @var ResponseInterface $response */
        $response = call_user_func($app);

        $this->assertSame(HttpStatus::NOT_IMPLEMENTED,
                          $response->getStatusCode());

        $this->assertStringContainsString(
            'No Routes',
            (string)$response->getBody());
    }

    public function _renderer(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
