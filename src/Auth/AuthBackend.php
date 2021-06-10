<?php

namespace Koded\Framework\Auth;

interface AuthBackend
{
    /**
     * Process the credentials for the required authentication
     * type on the server side by using a storage capable of
     * persisting the credentials.
     *
     * Returns NULL if authentication failed.
     *
     * @param string $type Authorization type
     * @param string $credentials The authentication credentials
     * @return object|null An object that represents the
     *                     authenticated caller (ex. user object)
     */
    public function __invoke(string $type, string $credentials): ?object;
}
