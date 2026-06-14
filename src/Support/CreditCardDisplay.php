<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

/**
 * Shared credit-card preview masking for PHP SSR and JS parity.
 */
class CreditCardDisplay
{
    /**
     * Bullet mask — use mb_substr (not substr) when padding groups in PHP.
     */
    public const string MASK_CHAR = '•';

    public const int MASKED_DIGIT_COUNT = 16;

    public static function maskedNumber(string $digits): string
    {
        $digits = preg_replace('/\D/', '', $digits) ?? '';

        $parts = [];

        for ($index = 0; $index < self::MASKED_DIGIT_COUNT; $index += 4) {
            $chunk = mb_substr($digits, $index, 4, 'UTF-8');
            $mask = str_repeat(self::MASK_CHAR, 4);
            $parts[] = mb_substr($chunk.$mask, 0, 4, 'UTF-8');
        }

        return implode(' ', $parts);
    }

    public static function maskedCvv(string $cvv): string
    {
        $cvv = preg_replace('/\D/', '', $cvv) ?? '';

        if ($cvv === '') {
            return str_repeat(self::MASK_CHAR, 3);
        }

        $length = str_starts_with($cvv, '3') ? 4 : 3;

        return self::padEnd($cvv, $length);
    }

    public static function sanitizeDigits(string $value, int $maxLength = 19): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        return substr($digits, 0, $maxLength);
    }

    public static function formatNumberInput(string $value): string
    {
        $digits = self::sanitizeDigits($value);

        if ($digits === '') {
            return '';
        }

        return trim(chunk_split($digits, 4, ' '));
    }

    protected static function padEnd(string $value, int $length): string
    {
        $missing = $length - mb_strlen($value, 'UTF-8');

        if ($missing <= 0) {
            return mb_substr($value, 0, $length, 'UTF-8');
        }

        return $value.str_repeat(self::MASK_CHAR, $missing);
    }
}
