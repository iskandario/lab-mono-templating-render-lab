<?php

declare(strict_types=1);

namespace infrastructure\bootstrap;

final readonly class PostgresConfig
{
    public function __construct(
        public string $dbname,
        public string $user,
        public string $password,
        public string $host = '127.0.0.1',
        public int $port = 5432,
        public string $sslmode = 'prefer',
        public string $sessionTtlSpec = 'P7D',
        public string $jwtSecret = 'dev-only-jwt-secret-change-me-32chars',
        public string $passwordPepper = 'dev-only-password-pepper-change-me-32chars',
        public int $passwordWorkFactor = 11,
        public string $cookieName = 'auth_token',
        public string $cookiePath = '/',
        public bool $cookieHttpOnly = true,
        public bool $cookieSecure = false,
        public string $cookieSameSite = 'lax'
    ) {
    }

    /**
     * @param array<string, string> $env
     */
    public static function fromEnv(array $env): self
    {
        return new self(
            dbname: $env['POSTGRES_DB'],
            user: $env['POSTGRES_USER'],
            password: $env['POSTGRES_PASSWORD'],
            host: $env['POSTGRES_HOST'] ?? '127.0.0.1',
            port: isset($env['POSTGRES_PORT']) ? (int)$env['POSTGRES_PORT'] : 5432,
            sslmode: $env['POSTGRES_SSLMODE'] ?? 'prefer',
            sessionTtlSpec: $env['SESSION_TTL_SPEC'] ?? 'P7D',
            jwtSecret: self::secret($env['JWT_SECRET'] ?? 'dev-only-jwt-secret-change-me-32chars', 'JWT_SECRET'),
            passwordPepper: self::secret($env['PASSWORD_PEPPER'] ?? 'dev-only-password-pepper-change-me-32chars', 'PASSWORD_PEPPER'),
            passwordWorkFactor: self::workFactor($env['PASSWORD_WORK_FACTOR'] ?? '11'),
            cookieName: trim($env['COOKIE_NAME'] ?? 'auth_token'),
            cookiePath: trim($env['COOKIE_PATH'] ?? '/'),
            cookieHttpOnly: self::boolFromEnv($env['COOKIE_HTTPONLY'] ?? 'true'),
            cookieSecure: self::boolFromEnv($env['COOKIE_SECURE'] ?? ($env['SESSION_COOKIE_SECURE'] ?? 'false')),
            cookieSameSite: self::sameSite($env['COOKIE_SAMESITE'] ?? 'lax')
        );
    }

    private static function boolFromEnv(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private static function secret(string $value, string $name): string
    {
        $value = trim($value);
        if (strlen($value) < 32) {
            throw new \InvalidArgumentException($name . ' must be at least 32 characters long.');
        }

        return $value;
    }

    private static function sameSite(string $value): string
    {
        $value = strtolower(trim($value));
        if (!in_array($value, ['lax', 'strict', 'none'], true)) {
            throw new \InvalidArgumentException('COOKIE_SAMESITE must be one of lax, strict, none.');
        }

        return $value;
    }

    private static function workFactor(string $value): int
    {
        $workFactor = (int)$value;
        if ($workFactor < 4 || $workFactor > 31) {
            throw new \InvalidArgumentException('PASSWORD_WORK_FACTOR must be between 4 and 31.');
        }

        return $workFactor;
    }
}
