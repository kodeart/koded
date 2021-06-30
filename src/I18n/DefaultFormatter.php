<?php

namespace Koded\Framework\I18n;

final class DefaultFormatter implements I18nFormatter
{
    public function format(string $string, array $arguments): string
    {
        return $arguments ? \vsprintf($string, $arguments) : $string;
    }
}
