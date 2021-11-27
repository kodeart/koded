<?php

namespace Tests\Koded\Framework\I18n;

use Koded\Framework\I18n\{I18n, I18nCatalog};
use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;

class LocaleTest extends TestCase
{
    public function test_default_locale()
    {
        new I18n(I18nCatalog::new(new Config));
        $this->assertSame(I18nCatalog::DEFAULT_LOCALE, I18n::locale());
    }

    public function test_other_locale()
    {
        $config = new Config;
        $config->set('translation.locale', 'mk_MK');
        $config->set('translation.dir', __DIR__ . '/../Fixtures');

//        dd(new I18n(I18nCatalog::new($config)));
        $this->assertSame('mk_MK', I18n::locale());
    }

    protected function setUp(): void
    {
        $this->markTestSkipped();
    }
}
