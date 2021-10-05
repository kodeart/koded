
Formatters
==========

String formatters are used to replace the translation arguments
provided in the `__(string, arguments)` function. The default 
formatter is `DefaultFormatter` and it's set in the [Koded `DIModule`](index.md#configuration).

!!! warning "Choose one"
    Once you start with translations, the format of the strings
    with arguments matters, because the argument replacement is
    different for `DefaultFormatter` and `StrtrFormatter`.

### DefaultFormatter

This formatter uses the [vsprintf][vsprintf] PHP function
to replace the string arguments with values.

### StrtrFormatter

This formatter uses the [strtr][strtr] PHP function to 
replace the string arguments with values.


[vsprintf]: https://php.net/vsprintf
[strtr]: https://php.net/strtr
