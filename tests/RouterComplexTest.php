<?php

namespace Tests\Koded\Framework;

use Koded\Caching\Client\MemoryClient;
use Koded\Http\HTTPConflict;
use Koded\Framework\Router;
use Koded\Stdlib\UUID;
use PHPUnit\Framework\TestCase;
use Throwable;

class RouterComplexTest extends TestCase
{
    use ObjectPropertyTrait;

    private Router $router;

    public function test_parameters_1()
    {
        $this->router->route('/api/{id:int}/collection/{uuid:uuid}', function() {});

        $uuid = UUID::v4();
        $match = $this->router->match("/api/462/collection/$uuid");

        $this->assertSame(
            '~^/api/(?P<id>\-?\d+)/collection/(?P<uuid>[a-f0-9]{8}\-[a-f0-9]{4}\-[1345][a-f0-9]{3}\-[a-f0-9]{4}\-[a-f0-9]{12})$~',
            $match['regex']);

        $this->assertSame(
            '/api/:int/collection/:uuid',
            $match['identity']);

        $this->assertSame(462, $match['params']->id);
        $this->assertSame($uuid, $match['params']->uuid);
    }

    public function test_parameters_2()
    {
        $this->router->route('/{lat:float}/{lon:regex:\d+.\d+}.{ext:regex:xml|json}', function() {});
        $match = $this->router->match('/1.23/4.56.xml');

        $this->assertSame(
            '~^/(?P<lat>(\-?\d*\.\d+))/(?P<lon>\d+.\d+).(?P<ext>xml|json)$~',
            $match['regex']);

        $this->assertSame(
            '/:float/\d+.\d+.xml|json',
            $match['identity']);

        $this->assertSame(
            ['lat' => 1.23, 'lon' => 4.56, 'ext' => 'xml'],
            (array)$match['params']);
    }

    public function test_duplicate_parameter_names()
    {
        try {
            $this->router->route('/{id:int}/{id:float}', function() {});
        } catch (Throwable $ex) {
            $this->assertInstanceOf(HTTPConflict::class, $ex);
            $this->assertStringContainsString(
                'PCRE compilation error',
                $ex->getMessage());
        }
    }

    public function test_duplicate_parameter_path_type()
    {
        try {
            $this->router->route('/{path1:path}/{path2:path}', function() {});
        } catch (Throwable $ex) {
            $this->assertInstanceOf(HTTPConflict::class, $ex);
            $this->assertStringContainsString(
                'Only one "path"',
                call_user_func([$ex, 'getDetail']));
        }
    }

    public function test_mixed_string_and_path_parameter_types()
    {
        try {
            $this->router->route('/{version}/{path:path}', function() {});
            $this->router->route('/{param1}/{param2}', function() {});
        } catch (HTTPConflict $ex) {

            $this->assertStringContainsString(
                'Detected a multiple route definitions',
                $ex->getDetail());

            $this->assertSame(
                '/{param1}/{param2}',
                $ex->getInstance());

            $members = $this->objectProperty($ex, 'members');
            $this->assertArrayHasKey('conflict-route', $members);
            $this->assertSame(
                ['/{param1}/{param2}' => '/{version}/{path:path}'],
                $members['conflict-route']);
        }
    }

    public function test_unsupported_parameter_type()
    {
        try {
            $this->router->route('/{param:regexeses:\s+}', function() {});
        } catch (HTTPConflict $ex) {
            $this->assertStringContainsString(
                "Invalid route parameter type 'regexeses'",
                $ex->getTitle());

            $this->assertStringContainsString(
                'Use one of the supported parameter types',
                $ex->getDetail());

            $members = $this->objectProperty($ex, 'members');
            $this->assertArrayHasKey('supported-types', $members);
            $this->assertSame(
                [
                    'str',
                    'int',
                    'float',
                    'path',
                    'regex',
                    'uuid',
                ],
                $members['supported-types']);
        }
    }

    public function test_invalid_parameter_regex_type()
    {
        try {
            $this->router->route('/{param:regex}', function() {});
        } catch (HTTPConflict $ex) {
            $this->assertStringContainsString(
                'Invalid route. No regular expression',
                $ex->getTitle());

            $this->assertStringContainsString(
                'Provide a proper PCRE regular expression',
                $ex->getDetail());
        }
    }

    public function test_invalid_parameter_regex_value()
    {
        try {
            $this->router->route('/{param:regex:}', function() {});
        } catch (HTTPConflict $ex) {
            $this->assertStringContainsString(
                'Invalid route. No regular expression provided',
                $ex->getTitle());

            $this->assertStringContainsString(
                'Provide a proper PCRE regular expression',
                $ex->getDetail());
        }
    }

    public function test_parameters_with_str_and_path()
    {
        $this->router->route("/{version}/{path:path}/{id:regex:\d+}", function() {});
        $match = $this->router->match('/v1/bar/baz/qux/123');

        $this->assertSame('v1', $match['params']->version);
        $this->assertSame('bar/baz/qux', $match['params']->path);
        $this->assertSame(123, $match['params']->id);
    }

    public function test_router_fails_to_differentiate_various_regex_forms_with_same_effect()
    {
        $IP_ADDRESS_REGEX_1 = '(?P<addr>([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3}))\.(?P<mime>xml|json)';
        $IP_ADDRESS_REGEX_2 = '(?P<addr>([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3}))';
        $IP_ADDRESS_REGEX_3 = '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}';

        $this->router->route("/{ip:regex:$IP_ADDRESS_REGEX_1}", function() {});
        $this->router->route("/{ip:regex:$IP_ADDRESS_REGEX_2}.{mime:regex:json|xml}", function() {});
        $this->router->route("/{ip:regex:$IP_ADDRESS_REGEX_3}", function() {});

        $match1 = $this->router->match('/127.0.0.1.xml');
        $this->assertSame('127.0.0.1.xml', $match1['params']->ip);
        $this->assertSame('127.0.0.1', $match1['params']->addr);
        $this->assertSame('xml', $match1['params']->mime);

        $match2 = $this->router->match('/127.0.0.1.xml');
        $this->assertEquals(
            $match1,
            $match2,
            'The second route is skipped because it is essentially the same as the first');

        $match3 = $this->router->match('/127.0.0.1.xml');
        $this->assertEquals(
            $match1,
            $match3,
            'The third route is skipped because it is essentially the same as the first');

        $this->assertEquals(
            $match2,
            $match3,
            'The second and third routes are skipped because they are essentially the same as the first');
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
