<?php

namespace Tests\Koded\Framework;

use Koded\Framework\App;
use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;
use function Koded\Stdlib\env;

class DIModuleConfigurationTest extends TestCase
{
    use ObjectPropertyTrait;

    /**
     * @dataProvider configs
     */
    public function test_loading_configuration($config)
    {
        $app = (new App(
            config: $config,
        ));

        /** @var Config $config */
        $config = $this
            ->objectProperty($app, 'container')
            ->get(Config::class);

        $this->assertSame('bar', $config->get('test.foo'));
        $this->assertSame('BAR', env('TEST_FOO'),
            'ENV variables are loaded from .env in the configured root directory');

        $this->assertSame([
            __DIR__ . '/Fixtures/test-autoloader.php',
        ], $config->get('autoloaders'));

        $this->assertTrue(class_exists(\TestAutoloadedClass::class),
            'Classes are loaded from "autoloaders" directive');
    }

    public function configs()
    {
        return [
            [__DIR__ . '/Fixtures/test-conf.php'],
            [(new Config(__DIR__ . '/Fixtures'))->fromPhpFile(__DIR__ . '/Fixtures/test-conf.php')]
        ];
    }
}
