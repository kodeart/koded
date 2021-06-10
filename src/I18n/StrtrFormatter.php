<?php

namespace Koded\Framework\I18n;

final class StrtrFormatter implements I18nFormatter
{
    public function format(string $string, array $arguments = []): string
    {
        return $arguments ? \strtr($string, $arguments) : $string;
    }
}
