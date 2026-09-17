<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use InvalidArgumentException;
use libphonenumber\geocoding\PhoneNumberOfflineGeocoder;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\PhoneNumberToTimeZonesMapper;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

/**
 * PHP-side libphonenumber enrichment (carrier / geo / time zones / formats).
 * Kept out of the Alpine path — zero phone-lib JS budget impact.
 */
final class PhoneNumberInsights
{
    /**
     * @param  list<PhoneNumberType|string|int>|null  $types
     * @return list<PhoneNumberType>|null
     */
    public static function normalizeAllowedTypes(?array $types): ?array
    {
        if ($types === null || $types === []) {
            return null;
        }

        $resolved = [];

        foreach ($types as $type) {
            $resolved[] = self::coerceType($type);
        }

        return array_values(array_unique($resolved, SORT_REGULAR));
    }

    public static function coerceType(PhoneNumberType|string|int $type): PhoneNumberType
    {
        if ($type instanceof PhoneNumberType) {
            return $type;
        }

        if (is_int($type)) {
            $byValue = PhoneNumberType::tryFrom($type);

            if ($byValue instanceof PhoneNumberType) {
                return $byValue;
            }

            throw new InvalidArgumentException("Unknown phone number type value [{$type}].");
        }

        $name = strtoupper(str_replace(['-', ' '], '_', $type));

        foreach (PhoneNumberType::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Unknown phone number type [{$type}].");
    }

    /**
     * When MOBILE or FIXED_LINE is allowed, also accept FIXED_LINE_OR_MOBILE unless strict.
     *
     * @param  list<PhoneNumberType>  $types
     * @return list<PhoneNumberType>
     */
    public static function expandTypesForValidation(array $types, bool $strict = false): array
    {
        if ($strict) {
            return $types;
        }

        $expanded = $types;

        if (in_array(PhoneNumberType::MOBILE, $types, true) || in_array(PhoneNumberType::FIXED_LINE, $types, true)) {
            $expanded[] = PhoneNumberType::FIXED_LINE_OR_MOBILE;
        }

        return array_values(array_unique($expanded, SORT_REGULAR));
    }

    /**
     * @param  list<PhoneNumberType>  $allowed
     */
    public static function typeIsAllowed(PhoneNumberType $type, array $allowed, bool $strict = false): bool
    {
        return in_array($type, self::expandTypesForValidation($allowed, $strict), true);
    }

    public static function nationalDigits(string $national): string
    {
        return preg_replace('/\D/', '', $national) ?? '';
    }

    public static function formatNationalDisplay(PhoneNumber $parsed, PhoneNumberUtil $util, bool $digitsOnly): string
    {
        $national = $util->format($parsed, PhoneNumberFormat::NATIONAL);

        return $digitsOnly ? self::nationalDigits($national) : $national;
    }

    /**
     * @param  list<string>  $formats  international|rfc3966
     * @param  list<string>  $metadata  carrier|geo|timezones|type
     * @return array<string, mixed>
     */
    public static function enrich(
        PhoneNumber $parsed,
        PhoneNumberUtil $util,
        array $formats = [],
        array $metadata = [],
        ?string $locale = null,
    ): array {
        $extra = [];
        $locale = filled($locale) ? str_replace('_', '-', $locale) : 'en';
        $lang = strtolower(explode('-', $locale)[0] ?: 'en');

        foreach ($formats as $format) {
            $key = strtolower((string) $format);

            $extra[$key] = match ($key) {
                'international' => $util->format($parsed, PhoneNumberFormat::INTERNATIONAL),
                'rfc3966' => $util->format($parsed, PhoneNumberFormat::RFC3966),
                default => throw new InvalidArgumentException("Unsupported phone format enrichment [{$format}]."),
            };
        }

        foreach ($metadata as $item) {
            $key = strtolower((string) $item);

            $extra[$key] = match ($key) {
                'carrier' => PhoneNumberToCarrierMapper::getInstance()->getNameForNumber($parsed, $lang) ?: null,
                'geo' => PhoneNumberOfflineGeocoder::getInstance()->getDescriptionForNumber($parsed, $lang) ?: null,
                'timezones' => array_values(PhoneNumberToTimeZonesMapper::getInstance()->getTimeZonesForNumber($parsed)),
                'type' => $util->getNumberType($parsed)->name,
                default => throw new InvalidArgumentException("Unsupported phone metadata enrichment [{$item}]."),
            };
        }

        return $extra;
    }
}
