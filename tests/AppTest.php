<?php

namespace Tests\Koded\Framework;

use Koded\Framework\App;
use PHPUnit\Framework\TestCase;
use function date_default_timezone_get;
use function date_default_timezone_set;

class AppTest extends TestCase
{
    public function test_construct_sets_timezone_to_utc()
    {
        date_default_timezone_set('Europe/Berlin');

        new App;

        $this->assertEquals('UTC', date_default_timezone_get(),
            'App instance always sets the default timezone to UTC');
    }
}
