<?php

namespace Koded\Framework\Auth;

/**
 * Implements Simple Token Based Authentication.
 *
 * Clients should authenticate by passing the value key in the
 * "Authorization" HTTP header, prepended with the string "Token "
 *
 *      Authorization: Token ac11d0618fc73ecb05ac96b905553bd506879de7
 */
class TokenAuthProcessor extends BearerAuthProcessor
{
    public function getTokenPrefix(): string
    {
        return 'Token';
    }
}
