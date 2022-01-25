<?php

namespace Koded\Framework\Auth;

use function Koded\Session\session;
use function password_hash;

/**
 * A basic authentication storage that uses the
 * PHP session for the user object.
 *
 * The format of the credentials session key is:
 *
 *  `<type>.<password_hash(credentials)>`
 *
 * Example: Bearer.a94a8fe5ccb19ba61c4c0873d391e987982fbbd3
 * Example: Token.$2y$10$FRmWYon7sMY4lZKW2D3NZueT3iUAsQj.MjfOSbukrBpLI9Qce11JO
 */
class SessionAuthBackend implements AuthBackend
{
    public function __invoke(string $type, string $credentials): ?object
    {
        return session()->get($type . '.' . password_hash($credentials, PASSWORD_DEFAULT));
    }
}
