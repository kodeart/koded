<?php

namespace Koded\Framework\Auth;

/**
 * Interface AuthProcessor
 *
 * @link https://tools.ietf.org/html/rfc7235#section-4.1
 */
interface AuthProcessor
{
    /**
     * Executes the authentication process with the corresponding backend provider.
     *
     * @param AuthBackend $backend The authentication backend provider
     * @param string      $credentials Authentication credentials that will be processed by the backend
     * @return object|null Some app object that represents the authenticated entity (ex. user)
     */
    public function authenticate(AuthBackend $backend, string $credentials): ?object;

    /**
     * Authentication type (ex. Basic, Token, JWT, etc)
     * @return string
     */
    public function getTokenPrefix(): string;
}
