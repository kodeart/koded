<?php

namespace Koded\Framework\I18n;

interface I18nFormatter
{
    /**
     * Message formatter for argument replacement in the message.
     *
     * @param string $string
     * @param array  $arguments
     * @return string The message with applied arguments (if any)
     */
    public function format(string $string, array $arguments): string;
}
