<?php

namespace Koded\Framework\I18n;

class I18n
{
    /** @var I18nCatalog[] */
    private static array $catalogs = [];
    private array $c;

    public function __construct(I18nCatalog $catalog)
    {
        static::$catalogs[$catalog->locale()] = $catalog;
        $this->c = self::$catalogs;
    }

    public static function translate(
        string $string,
        array $arguments = [],
        string $locale = I18nCatalog::DEFAULT_LOCALE): string
    {
        try {
            return static::$catalogs[$locale]->translate('messages', $string, $arguments);
        } catch (\Throwable $e) {
            \error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return \vsprintf($string, $arguments);
        }
    }

    public static function locale(): string
    {
        return current(static::$catalogs)->locale();
    }
}
