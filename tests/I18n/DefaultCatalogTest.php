<?php

namespace Tests\Koded\Framework\I18n;

use Koded\Framework\I18n\{DefaultCatalog, DefaultFormatter, I18n, I18nCatalog};
use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;
use Tests\Koded\Framework\ObjectPropertyTrait;

class DefaultCatalogTest extends TestCase
{
    use ObjectPropertyTrait;

    public function test_default_catalog_object(): I18n
    {
        $i18n = new I18n(I18nCatalog::new(new Config));
        $catalogs = $this->property($i18n, 'catalogs');

        $this->assertArrayHasKey(I18nCatalog::DEFAULT_LOCALE, $catalogs,
                                 'The default locale is created');

        /** @var I18nCatalog $catalog */
        $catalog = $catalogs[I18nCatalog::DEFAULT_LOCALE];

        $this->assertInstanceOf(DefaultCatalog::class, $catalog,
                                'The default catalog is a DefaultCatalog instance');

        $this->assertInstanceOf(DefaultFormatter::class, $this->property($catalog, 'formatter'),
                                'The default message formatter is a DefaultFormatter instance');

        $this->assertSame(I18nCatalog::DEFAULT_LOCALE, $catalog->locale(),
                          'Default catalog locale is en_US');

        $this->assertStringContainsString(
            'src/I18n/../locales/',
            $this->property($catalog, 'dir'),
            'The default path for translation files is set to "src/locales" path'
        );

        return $i18n;
    }

    /**
     * @depends test_default_catalog_object
     */
    public function test_translation_method(I18n $instance)
    {
        $this->assertSame('fubar 123', $instance::translate('fubar %s', ['123']),
                          'String replacement, defaults to en_US');

        $this->assertSame('fubar 123', $instance::translate('fubar %s', ['123'], 'de_DE'),
                          'No de_DE locale, defaults to en_US');

        $this->assertSame('fubar %s', $instance::translate('fubar %s'),
                          'No replacement arguments, translate as-is');
    }

    /**
     * @depends test_default_catalog_object
     */
    public function test_translation_function()
    {
        $this->assertSame('fubar 123', __('fubar %s', ['123']),
                          'Catalog is in a static registry, expecting translation');

        $this->assertSame('fubar 123', __('fubar %s', ['123'], 'de_DE'),
                          'No de_DE locale, defaults to en_US');

        $this->assertSame('fubar %s', __('fubar %s'),
                          'No replacement arguments, translate as-is');
    }
}
