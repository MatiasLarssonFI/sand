<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Shared\AuthorizationException;

final class SessionCsrfTokenManager
{
    private const TOKEN_KEY = '_csrf_token';

    public function token(): string
    {
        if (!isset($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    public function assertValid(?string $token): void
    {
        if (!is_string($token) || !hash_equals($this->token(), $token)) {
            throw new AuthorizationException('Invalid CSRF token.');
        }
    }
}
