<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Barcode;

use Bjanczak\FilamentFlexFields\Enums\BarcodeFormat;

class BarcodeStateNormalizer
{
    /**
     * @return array{value: string, format: string|null}|null
     */
    public static function normalize(mixed $state, bool $storeDetectedFormat = false): ?array
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_string($state)) {
            $value = trim($state);

            if ($value === '') {
                return null;
            }

            return [
                'value' => $value,
                'format' => $storeDetectedFormat ? BarcodeValidator::detectFormat($value)?->value : null,
            ];
        }

        if (! is_array($state)) {
            return null;
        }

        $value = trim((string) ($state['value'] ?? $state[0] ?? ''));

        if ($value === '') {
            return null;
        }

        $format = $state['format'] ?? null;

        if (is_string($format) && $format !== '') {
            $resolved = BarcodeFormat::tryFrom($format);

            $format = $resolved?->value;
        } else {
            $format = null;
        }

        if ($storeDetectedFormat && $format === null) {
            $format = BarcodeValidator::detectFormat($value)?->value;
        }

        return [
            'value' => $value,
            'format' => $storeDetectedFormat ? $format : null,
        ];
    }

    public static function extractValue(mixed $state): ?string
    {
        $normalized = self::normalize($state);

        return $normalized['value'] ?? null;
    }

    public static function extractFormat(mixed $state): ?string
    {
        $normalized = self::normalize($state, storeDetectedFormat: true);

        return $normalized['format'] ?? null;
    }

    /**
     * @return string|array{value: string, format: string|null}|null
     */
    public static function dehydrate(mixed $state, bool $storeDetectedFormat = false): string|array|null
    {
        $normalized = self::normalize($state, $storeDetectedFormat);

        if ($normalized === null) {
            return null;
        }

        if (! $storeDetectedFormat) {
            return $normalized['value'];
        }

        return [
            'value' => $normalized['value'],
            'format' => $normalized['format'],
        ];
    }

    public static function isEmpty(mixed $state): bool
    {
        return self::normalize($state) === null;
    }
}
