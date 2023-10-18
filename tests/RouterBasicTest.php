<?php

namespace Tests\Koded\Framework;

use Closure;
use Koded\Caching\Client\MemoryClient;
use Koded\Framework\Router;
use Koded\Http\HTTPConflict;
use Koded\Http\Interfaces\HttpStatus;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Tests\Koded\Framework\Fixtures\TestResource;
use function crc32;

class RouterBasicTest extends TestCase
{
    use ObjectPropertyTrait;

    public function test_same_route_definitions()
    {
        $router = new Router(new MemoryClient);

        try {
            $resource = function () { };
            $router->route('/{fubar}/', $resource);
            $router->route('/{param}/', $resource);

        } catch (HTTPConflict $ex) {
            $this->assertSame(HttpStatus::CONFLICT, $ex->getStatusCode());
            $this->assertSame('/{param}/', $ex->getInstance());
            $this->assertSame('Duplicate route', $ex->getTitle());
            $this->assertSame('Duplicate route', $ex->getMessage());

            $members = $this->objectProperty($ex, 'members');
            $this->assertArrayHasKey('conflict-route', $members);
            $this->assertEquals(
                ['/{param}/' => '/{fubar}/'],
                $members['conflict-route']);
        }
    }

    public function test_explicit_route_with_catch_all_of_same_type()
    {
        $router = new Router(new MemoryClient);

        $resource = function () { };
        $router->route('/fubar', $resource);
        $router->route('/{param}', $resource);

        $match = $router->match('/fubar');
        $this->assertSame('~^/fubar$~ui', $match['regex']);
        $this->assertSame('/fubar', $match['identity']);

        $match = $router->match('/fubar/');
        $this->assertSame('fubar/', $match['params']->param);
        $this->assertSame(
            '~^/(?P<param>.+?)$~ui',
            $match['regex'],
            'Trailing slash processes the templates differently; direct URI fails because of this');

        $match = $router->match('/barqux');
        $this->assertSame('~^/(?P<param>.+?)$~ui', $match['regex']);
        $this->assertSame(
            'barqux',
            $match['params']->param,
            'Matches the arbitrary string; with no trailing slash in the URI it matches the regex');

        $index = $this->objectProperty($router, 'index');
        $this->assertCount(
            2,
            $index,
            'Explicit URI templates do not throw multiple definitions exception');
    }

    /**
     * @depends test_explicit_route_with_catch_all_of_same_type
     */
    public function test_reverse_explicit_route_with_catch_all_of_same_type()
    {
        $router = new Router(new MemoryClient);

        $resource = function () { };
        $router->route('/{param}', $resource);
        $router->route('/fubar', $resource);

        $match = $router->match('/fubar');
        $this->assertSame('~^/fubar$~ui', $match['regex']);
        $this->assertSame('/fubar', $match['identity']);

        $match = $router->match('/barqux');
        $this->assertSame('~^/(?P<param>.+?)$~ui', $match['regex']);
        $this->assertSame(
            'barqux',
            $match['params']->param,
            'Matches the arbitrary string');
    }

    public function test_callable_resources_are_not_cached()
    {
        /** @var CacheInterface $cache */

        $template = '/fubar/';
        $templateId = 'r.' . crc32($template);

        $router = new Router(new MemoryClient);
        $router->route($template, function () { });

        $cache = $this->objectProperty($router, 'cache');
        $this->assertEmpty(
            $cache->get($templateId)['resource'],
            'Non-cacheable resources are not in the cache index');

        $callbacks = $this->objectProperty($router, 'callback');

        $this->assertInstanceOf(
            Closure::class,
            $callbacks[$templateId]['resource'],
            'Non-cacheable resources are stored in the router callback registry');

        $this->assertFalse($this->objectProperty($router, 'cached'));
    }

    public function test_resource_class_cache()
    {
        /** @var CacheInterface $cache */

        $template = '/fubar/';
        $templateId = 'r.' . crc32($template);

        $router = new Router(new MemoryClient);
        $router->route($template, TestResource::class);

        $cache = $this->objectProperty($router, 'cache');

        $this->assertSame(
            TestResource::class,
            $cache->get($templateId)['resource'],
            'Resource classes are stored by FQCN');
    }

    public function test_match_without_named_parameters()
    {
        $router = new Router(new MemoryClient);
        $router->route('/\d+', new \stdClass);
        $match = $router->match('/123');

        $this->assertArrayHasKey('params', $match);
        $this->assertSame([], $match['params'],
            'Path matches, but there are no named parameters in URI template');
    }
}
