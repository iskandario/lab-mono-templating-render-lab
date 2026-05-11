<?php

declare(strict_types=1);

namespace infrastructure\presentation\http;

use DateTimeImmutable;
use DateTimeInterface;

final class SessionCookieFactory
{
    public function __construct(
        private readonly string $name = 'auth_token',
        private readonly string $path = '/',
        private readonly bool $httpOnly = true,
        private readonly bool $secure = false,
        private readonly string $sameSite = 'lax'
    ) {
    }

    public function issue(string $token, DateTimeInterface $expiresAt): string
    {
        $attributes = 'Path=%s; SameSite=%s';
        if ($this->httpOnly) {
            $attributes .= '; HttpOnly';
        }
        if ($this->secure) {
            $attributes .= '; Secure';
        }

        return sprintf(
            '%s=%s; ' . $attributes,
            rawurlencode($this->name),
            rawurlencode($token),
            $this->path,
            ucfirst(strtolower($this->sameSite))
        );
    }

    public function expire(): string
    {
        return sprintf(
            '%s=; Path=%s; Expires=%s; Max-Age=0; SameSite=%s%s%s',
            rawurlencode($this->name),
            $this->path,
            gmdate('D, d M Y H:i:s T', (new DateTimeImmutable('@0'))->getTimestamp()),
            ucfirst(strtolower($this->sameSite)),
            $this->httpOnly ? '; HttpOnly' : '',
            $this->secure ? '; Secure' : ''
        );
    }
}
