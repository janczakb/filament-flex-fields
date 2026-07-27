<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

/**
 * Normalize values for Spatie locale storage / Filament field state.
 *
 * FlexRichEditor (TipTap) dehydrates as an array document — the old string-only
 * Spatie dehydrator turned that into null and wiped content on save.
 */
final class SpatieTranslatableState
{
    /**
     * Prepare a locale value for Spatie storage.
     */
    public static function dehydrate(mixed $state): mixed
    {
        if ($state === null) {
            return null;
        }

        if (is_array($state)) {
            return $state === [] ? null : $state;
        }

        if (! is_string($state)) {
            return null;
        }

        $trimmed = trim($state);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Restore a Spatie locale value into Filament field state.
     */
    public static function hydrate(mixed $translation): mixed
    {
        if ($translation === null) {
            return null;
        }

        if (is_array($translation)) {
            return $translation === [] ? null : $translation;
        }

        if (is_object($translation)) {
            return (array) $translation;
        }

        if (! is_string($translation)) {
            return null;
        }

        $trimmed = trim($translation);

        if ($trimmed === '') {
            return null;
        }

        // TipTap docs may have been json_encoded into the locale string.
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);

            if (is_array($decoded) && self::looksLikeTipTapDocument($decoded)) {
                return $decoded;
            }
        }

        return $trimmed;
    }

    /**
     * @param  array<mixed>  $value
     */
    public static function looksLikeTipTapDocument(array $value): bool
    {
        return ($value['type'] ?? null) === 'doc' && array_key_exists('content', $value);
    }
}
