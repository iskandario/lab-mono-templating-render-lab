<?php

declare(strict_types=1);

namespace infrastructure\support;

use application\service\PasswordHasherInterface;
use domain\common\exception\ValidationException;

final class NativePasswordHasher implements PasswordHasherInterface
{
    private const MIN_PASSWORD_LENGTH = 6;

    public function __construct(
        private readonly string $pepper = 'dev-only-password-pepper-change-me-32chars',
        private readonly int $workFactor = 11
    ) {
    }

    public function hash(string $plainText): string
    {
        $plainText = trim($plainText);
        if (strlen($plainText) < self::MIN_PASSWORD_LENGTH) {
            throw new ValidationException('account.password.too_short', 4428);
        }

        $hash = password_hash($this->peppered($plainText), PASSWORD_BCRYPT, ['cost' => $this->workFactor]);
        if (!is_string($hash) || $hash === '') {
            throw new \RuntimeException('account.password.hash_failed');
        }

        return $hash;
    }

    public function verify(string $plainText, string $hash): bool
    {
        $plainText = trim($plainText);
        if ($plainText === '') {
            return false;
        }

        if (password_verify($this->peppered($plainText), $hash)) {
            return true;
        }

        return false;
    }

    private function peppered(string $plainText): string
    {
        return base64_encode(hash_hmac('sha384', $plainText, $this->pepper, true));
    }
}
