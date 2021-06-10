<?php

namespace Koded\Framework\I18n;

class I18n
{
    /** @var I18nCatalog[] */
    private static array $catalogs = [];

    public function __construct(I18nCatalog $catalog)
    {
        self::$catalogs[$catalog->locale()] = $catalog;
    }

    public static function translate(
        string $string,
        array $arguments,
        string $locale): string
    {
        try {
            return self::$catalogs[$locale]->translate('messages', $string, $arguments);
        } catch (\Throwable $e) {
            \error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return \vsprintf($string, $arguments);
        }
    }
}
