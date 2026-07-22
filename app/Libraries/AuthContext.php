<?php

namespace App\Libraries;

/**
 * Holds the authenticated user for the lifetime of the current request.
 * Populated by App\Filters\TokenAuthFilter after validating the bearer token.
 */
class AuthContext
{
    private static ?array $user = null;

    public static function set(array $user): void
    {
        self::$user = $user;
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::$user['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::$user['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }
}
