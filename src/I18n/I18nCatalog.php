<?php

namespace Koded\Framework\I18n;

use Koded\Stdlib\Configuration;

abstract class I18nCatalog
{
    public const DEFAULT_LOCALE = 'en_US';
    protected const DEFAULT_DOMAIN = 'messages';

    protected I18nFormatter $formatter;
    protected string $locale = I18nCatalog::DEFAULT_LOCALE;
    protected string $dir = '';

    private function __construct(
        I18nFormatter $formatter,
        string $locale,
        string $dir)
    {
        $this->formatter = $formatter;
        $this->dir = \rtrim($dir, '/') . '/';
        $this->locale = $this->initialize(
            $this->supports($locale) ? $locale : static::DEFAULT_LOCALE
        ) ?: static::DEFAULT_LOCALE;
    }

    public static function new(Configuration $conf): I18nCatalog
    {
        /** @var static $catalog */
        /** @var static $self */
        $catalog = $conf->get('translation.catalog', DefaultCatalog::class);
        $formatter = $conf->get('translation.formatter', DefaultFormatter::class);
        $self = new $catalog(
            new $formatter,
            $conf->get('translation.locale', static::DEFAULT_LOCALE),
            $conf->get('translation.dir', __DIR__ . '/../locales')
        );
        if ($self->locale()) {
            return $self;
        }
        // Fallback to default catalog and locale
        return new $catalog(
            new DefaultFormatter,
            static::DEFAULT_LOCALE,
            __DIR__ . '/../locales'
        );
    }

    public function translate(
        string $domain,
        string $key,
        array $arguments = [],
        int $n = 0): string
    {
        return $this->formatter->format(
            $this->message($domain, $key, $n),
            $arguments
        );
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * Translates the message.
     *
     * @param string $domain
     * @param string $string
     * @param int    $n
     * @return string
     */
    abstract protected function message(string $domain, string $string, int $n): string;

    /**
     * Checks if the locale is supported by the catalog,
     * or other specific requirements for the catalog.
     *
     * @param string $locale
     * @return bool
     */
    abstract protected function supports(string $locale): bool;

    /**
     * Initialize the catalog object.
     *
     * @param string $locale Desired locale to be initialized
     * @return string|false Returns the set locale,
     *                      or FALSE if initialization fails.
     */
    abstract protected function initialize(string $locale): string|false;
}
