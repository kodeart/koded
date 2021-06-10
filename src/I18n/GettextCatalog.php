<?php

namespace Koded\Framework\I18n;

final class GettextCatalog extends I18nCatalog
{
    private array $domains = [];

    protected function message(string $domain, string $string, int $n): string
    {
        if (false === isset($this->domains[$domain])) {
            $this->dir = \bindtextdomain($domain, $this->dir);
            $this->domains[$domain = \textdomain($domain)] = true;
        }
        if ($n > 0) {
            return self::DEFAULT_DOMAIN === $domain
                ? \ngettext($string, $string, $n)
                : \dngettext($domain, $string, $string, $n);
        }
        return self::DEFAULT_DOMAIN === $domain
            ? \gettext($string)
            : \ngettext($domain, $string, $n);
    }

    protected function supports(string $locale): bool
    {
        return \function_exists('\bindtextdomain')
            && \setlocale(LC_MESSAGES, $locale . '.utf8');
    }

    protected function initialize(string $locale): string|false
    {
        return \setlocale(LC_MESSAGES, 0);
    }
}
