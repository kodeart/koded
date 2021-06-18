<?php

namespace Koded\Framework\Auth;

use Koded\Framework\HTTPUnauthorized;

/**
 * Implements Bearer based authentication.
 *
 * Clients should authenticate by passing the value key in the
 * "Authorization" HTTP header, prepended with the string "Bearer "
 *
 *      Authorization: Bearer ac11d0618fc73ecb05ac96b905553bd506879de7
 */
class BearerAuthProcessor extends BasicAuthProcessor
{
    public function authenticate(AuthBackend $backend, string $credentials): ?object
    {
        return $backend($this->getTokenPrefix(), $this->extractCredentials($credentials))
            ?? throw new HTTPUnauthorized(
                title: __('Authorization failed'),
                detail: __('Invalid authorization token'),
                type: 'https://kodedphp.github.io/auth/value',
            );
    }

    public function getTokenPrefix(): string
    {
        return 'Bearer';
    }
}
