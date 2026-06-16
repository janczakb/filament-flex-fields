<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Barcode;

use Bjanczak\FilamentFlexFields\Enums\BarcodeFormat;

class BarcodeValidator
{
    /**
     * @param  list<BarcodeFormat>  $allowedFormats
     */
    public static function validateValue(
        mixed $value,
        array $allowedFormats,
        bool $validateChecksum = false,
    ): ?string {
        if (! is_string($value)) {
            return __('filament-flex-fields::default.barcode_scanner.validation.invalid');
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if ($allowedFormats === []) {
            $allowedFormats = BarcodeFormat::cases();
        }

        $matched = null;

        foreach ($allowedFormats as $format) {
            if (self::matchesFormat($value, $format)) {
                $matched = $format;

                break;
            }
        }

        if ($matched === null) {
            return __('filament-flex-fields::default.barcode_scanner.validation.unrecognized');
        }

        if ($validateChecksum && self::supportsChecksum($matched) && ! self::isChecksumValid($value, $matched)) {
            return __('filament-flex-fields::default.barcode_scanner.validation.checksum');
        }

        return null;
    }

    public static function matchesFormat(string $value, BarcodeFormat $format): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        return match ($format) {
            BarcodeFormat::Qr => strlen($value) >= 1,
            BarcodeFormat::Ean13 => preg_match('/^\d{13}$/', $value) === 1,
            BarcodeFormat::Ean8 => preg_match('/^\d{8}$/', $value) === 1,
            BarcodeFormat::UpcA => preg_match('/^\d{12}$/', $value) === 1,
            BarcodeFormat::UpcE => preg_match('/^\d{6,8}$/', $value) === 1,
            BarcodeFormat::Code128 => preg_match('/^[\x20-\x7E]+$/', $value) === 1 && strlen($value) >= 1,
            BarcodeFormat::Code39 => preg_match('/^[0-9A-Z\-. $\/+%]+$/', $value) === 1 && strlen($value) >= 1,
            BarcodeFormat::Itf => preg_match('/^\d+$/', $value) === 1 && strlen($value) >= 4 && strlen($value) % 2 === 0,
            BarcodeFormat::Pdf417 => strlen($value) >= 4,
            BarcodeFormat::DataMatrix => strlen($value) >= 1,
        };
    }

    public static function detectFormat(string $value): ?BarcodeFormat
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        foreach ([
            BarcodeFormat::Ean13,
            BarcodeFormat::Ean8,
            BarcodeFormat::UpcA,
            BarcodeFormat::UpcE,
            BarcodeFormat::Itf,
            BarcodeFormat::Code39,
            BarcodeFormat::Code128,
            BarcodeFormat::Pdf417,
            BarcodeFormat::DataMatrix,
            BarcodeFormat::Qr,
        ] as $format) {
            if (self::matchesFormat($value, $format)) {
                return $format;
            }
        }

        return null;
    }

    public static function supportsChecksum(BarcodeFormat $format): bool
    {
        return in_array($format, [
            BarcodeFormat::Ean13,
            BarcodeFormat::Ean8,
            BarcodeFormat::UpcA,
            BarcodeFormat::UpcE,
        ], true);
    }

    public static function isChecksumValid(string $value, BarcodeFormat $format): bool
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        return match ($format) {
            BarcodeFormat::Ean13 => self::validateModulo10Checksum($digits, 13),
            BarcodeFormat::Ean8 => self::validateModulo10Checksum($digits, 8),
            BarcodeFormat::UpcA => self::validateModulo10Checksum($digits, 12),
            BarcodeFormat::UpcE => strlen($digits) >= 6 && self::validateModulo10Checksum(str_pad($digits, 8, '0', STR_PAD_LEFT), 8),
            default => true,
        };
    }

    public static function formatLabel(BarcodeFormat $format): string
    {
        return __("filament-flex-fields::default.barcode_scanner.formats.{$format->value}");
    }

    protected static function validateModulo10Checksum(string $digits, int $expectedLength): bool
    {
        if (strlen($digits) !== $expectedLength || ! ctype_digit($digits)) {
            return false;
        }

        $sum = 0;

        for ($index = 0; $index < $expectedLength - 1; $index++) {
            $weight = ($expectedLength - 1 - $index) % 2 === 0 ? 3 : 1;
            $sum += (int) $digits[$index] * $weight;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit === (int) $digits[$expectedLength - 1];
    }
}
