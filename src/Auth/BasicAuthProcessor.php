<?php

namespace Koded\Framework\Auth;

use Koded\Framework\{HTTPError, HTTPUnauthorized};

/**
 * Implements HTTP Basic Authentication
 * <http://tools.ietf.org/html/rfc2617>
 *
 * Clients should authenticate by passing the `base64` encoded credentials
 * `username:password` in the `Authorization` HTTP header, prepended with the
 * string specified in the setting `auth_header_prefix`. For example:
 *
 *      Authorization: Basic aGVsbG86d29ybGQ=
 */
class BasicAuthProcessor implements AuthProcessor
{
    public function authenticate(AuthBackend $backend, string $credentials): ?object
    {
        try {
            return $backend(
                $this->getTokenPrefix(),
                \join(':', $this->decodeCredentials($this->extractCredentials($credentials)))
            );
        } catch (HTTPError $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new HTTPUnauthorized(
                title: __('Authorization failed'),
                detail: $e->getMessage()
            );
        }
    }

    public function getTokenPrefix(): string
    {
        return 'Basic';
    }

    protected function extractCredentials(string $credentials): string
    {
        $credentials = \trim($credentials);
        empty($credentials) && throw new HTTPUnauthorized(
            title: __('Invalid authorization credentials'),
            detail: __('The authorization header is missing'),
            type: 'https://kodedphp.github.io/auth/header',
            headers: ['WWW-Authenticate' => $this->getTokenPrefix()]
        );
        $parts = \mb_split('\s', $credentials);

        \strtolower($parts[0]) !== \strtolower($this->getTokenPrefix()) && throw new HTTPUnauthorized(
            title: __('Authorization failed'),
            detail: __('Authorization header must start with %s', [$this->getTokenPrefix()]),
            type: 'https://kodedphp.github.io/auth/format',
            headers: ['WWW-Authenticate' => $this->getTokenPrefix()]
        );
        1 === \count($parts) && throw new HTTPUnauthorized(
            title: __('Authorization failed'),
            detail: __('Missing authorization value'),
            type: 'https://kodedphp.github.io/auth/value',
            headers: ['WWW-Authenticate' => $this->getTokenPrefix()]
        );
        2 < \count($parts) && throw new HTTPUnauthorized(
            title: __('Authorization failed'),
            detail: __('Authorization header contains extra values'),
            type: 'https://kodedphp.github.io/auth/format'
        );
        return $parts[1];
    }

    private function decodeCredentials(string $secret): array
    {
        $decoded = \base64_decode($secret, true);
        $decoded = \mb_split(':', $decoded);
        (false === $decoded || 2 !== \count($decoded)) && throw new HTTPUnauthorized(
            title: __('Authorization failed'),
            detail: __('Failed to process the authorization credentials'),
            type: 'https://kodedphp.github.io/auth/credentials'
        );
        return $decoded;
    }
}
