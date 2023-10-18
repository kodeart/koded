<?php

namespace Tests\Koded\Framework;

use Koded\Caching\Client\MemoryClient;
use Koded\Framework\Router;
use Koded\Stdlib\UUID;
use PHPUnit\Framework\TestCase;
use stdClass;

class RouterTemplateTypesTest extends TestCase
{
    private Router $router;

    public function test_uuid_type()
    {
        $uuid = UUID::v4();
        $this->router->route('/{param:uuid}', function() {});
        $match = $this->router->match("/$uuid");

        $this->assertSame($uuid, $match['params']->param);
    }

    public function test_integer_type()
    {
        $this->router->route('/{param:int}', function() {});
        $match = $this->router->match('/12345');

        $this->assertSame(12345, $match['params']->param);
    }

    public function test_float_type()
    {
        $this->router->route('/{lon:float}/{lat:float}', function() {});
        $match = $this->router->match('/12.345/67.890');

        // resolved parameters
        $expected = new stdClass;
        $expected->lon = 12.345;
        $expected->lat = 67.89;

        $this->assertEquals($expected, $match['params']);
    }

    public function test_regex_type()
    {
        $this->router->route('/{lon:regex:\d+\.\d+}/{lat:regex:\d+\.\d+}', function() {});
        $match = $this->router->match('/1.234/5.678');

        // resolved parameters
        $expected = new stdClass;
        $expected->lon = 1.234;
        $expected->lat = 5.678;

        $this->assertEquals($expected, $match['params']);
    }

    public function test_path_type()
    {
        $this->router->route('/{uri:path}', function() {});
        $match = $this->router->match('/foo/bar-123/baz.json');

        // resolved parameters
        $expected = new stdClass;
        $expected->uri = 'foo/bar-123/baz.json';

        $this->assertEquals($expected, $match['params']);
    }

    public function test_parameter_without_type()
    {
        $this->router->route('/{param}', function() {});
        $match = $this->router->match('/foo-bar-baz.xml');

        // resolved parameters
        $expected = new stdClass;
        $expected->param = 'foo-bar-baz.xml';

        $this->assertEquals($expected, $match['params']);
    }

    protected function setUp(): void
    {
        $this->router = new Router(new MemoryClient);
    }

    protected function tearDown(): void
    {
        unset($this->router);
    }
}
