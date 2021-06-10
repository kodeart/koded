<?php

namespace Koded\Framework\I18n;

class DefaultCatalog extends I18nCatalog
{
    private array $data = [];

    protected function message(string $domain, string $string, int $n): string
    {
        return $this->data[$domain][$string] ?? $string;
    }

    protected function supports(string $locale): bool
    {
        return true;
    }

    protected function initialize(string $locale): string|false
    {
        $catalog = $this->dir . $locale . '.php';
        try {
            /** @noinspection PhpIncludeInspection */
            $this->data = require $catalog;
            return $locale;
        } catch (\Throwable $e) {
            \error_log(\sprintf('[%s] Expects a catalog: %s. The error message was: %s',
                __CLASS__, $catalog, $e->getMessage())
            );
            return self::DEFAULT_LOCALE;
        }
    }
}
