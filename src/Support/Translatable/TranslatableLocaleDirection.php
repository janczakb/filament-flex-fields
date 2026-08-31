<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Bjanczak\FilamentFlexFields\Enums\TranslatableDirectionScope;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Forms\Components\Field;

final class TranslatableLocaleDirection
{
    /**
     * @param  list<string>|null  $rtlLocales
     */
    public static function resolveDirection(string $locale, ?array $rtlLocales = null): string
    {
        return self::isRtlLocale($locale, $rtlLocales) ? 'rtl' : 'ltr';
    }

    /**
     * @param  list<string>|null  $rtlLocales
     */
    public static function isRtlLocale(string $locale, ?array $rtlLocales = null): bool
    {
        $locale = strtolower(trim($locale));

        if ($locale === '') {
            return false;
        }

        /** @var list<string> $rtlLocales */
        $rtlLocales = array_map(
            static fn (string $code): string => strtolower(trim($code)),
            $rtlLocales ?? config('filament-flex-fields.translatable.rtl_locales', ['ar', 'he', 'fa', 'ur']),
        );

        if (in_array($locale, $rtlLocales, true)) {
            return true;
        }

        $primary = self::primaryLanguageSubtag($locale);

        return in_array($primary, $rtlLocales, true);
    }

    public static function applyToField(
        Field $field,
        string $locale,
        TranslatableDirectionScope $scope = TranslatableDirectionScope::Auto,
        ?array $rtlLocales = null,
    ): void {
        $direction = self::resolveDirection($locale, $rtlLocales);

        if ($scope === TranslatableDirectionScope::Field || (
            $scope === TranslatableDirectionScope::Auto && ! self::supportsInputDirection($field)
        )) {
            $field->extraAttributes(['dir' => $direction], merge: true);

            return;
        }

        $field->extraInputAttributes(['dir' => $direction], merge: true);
    }

    public static function supportsInputDirection(Field $field): bool
    {
        return in_array(HasExtraInputAttributes::class, class_uses_recursive($field), true);
    }

    private static function primaryLanguageSubtag(string $locale): string
    {
        $segment = strtok($locale, '-_');

        return is_string($segment) ? $segment : $locale;
    }
}
