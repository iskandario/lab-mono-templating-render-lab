<?php

declare(strict_types=1);

namespace infrastructure\presentation\http;

use DateTimeInterface;

final class JwtSessionTokenProcessor
{
    private const SESSION_ID_CLAIM = 'sid';
    private const EXPIRATION_CLAIM = 'exp';

    public function __construct(
        private readonly string $secret
    ) {
    }

    public function encode(string $sessionId, DateTimeInterface $expiresAt): string
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode([
            self::SESSION_ID_CLAIM => $sessionId,
            self::EXPIRATION_CLAIM => $expiresAt->getTimestamp(),
        ], JSON_THROW_ON_ERROR));
        $signature = $this->sign($header . '.' . $payload);

        return $header . '.' . $payload . '.' . $signature;
    }

    public function decodeSessionId(string $token): ?string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        if (!hash_equals($this->sign($header . '.' . $payload), $signature)) {
            return null;
        }

        try {
            $decoded = json_decode($this->base64UrlDecode($payload), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        $expiresAt = $decoded[self::EXPIRATION_CLAIM] ?? null;
        if (!is_int($expiresAt) && !is_float($expiresAt)) {
            return null;
        }

        if ($expiresAt <= time()) {
            return null;
        }

        $sessionId = $decoded[self::SESSION_ID_CLAIM] ?? null;

        return is_string($sessionId) && trim($sessionId) !== '' ? trim($sessionId) : null;
    }

    private function sign(string $value): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $value, $this->secret, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
