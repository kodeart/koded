<?php

namespace Tests\Koded\Framework;

use PHPUnit\Framework\TestCase;

class ResponderHeadTest extends TestCase
{
    public function test_make_sure_methods_are_set()
    {
        $response = head_response('/', ['POST'])();

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Allow', $headers);
        $this->assertSame(['POST'], $headers['Allow']);
    }

    public function test_set_default_methods_if_not_provided()
    {
        $response = head_response('/', [])();

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Allow', $headers);
        $this->assertSame(['HEAD', 'OPTIONS'], $headers['Allow']);
    }
}
