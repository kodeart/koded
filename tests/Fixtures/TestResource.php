<?php

namespace Tests\Koded\Framework\Fixtures;

use Koded\Http\Interfaces\Response;

class TestResource
{
    public function get(Response $response): Response
    {
        return $response;
    }

    public function post(Response $response): Response
    {
        return $response;
    }

    public function delete(Response $response): Response
    {
        return $response;
    }
}
