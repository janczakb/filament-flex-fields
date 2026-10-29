<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\TextInput;

use InvalidArgumentException;

/** Shared, bounded configuration contract for fluent API, render snapshots and builder. */
final class TextInputOptions
{
    public const array DEFAULTS = [
        'counterMode' => 'characters', 'counterDisplay' => 'used', 'counterWarningAt' => 0.85,
        'softLimit' => null, 'counterLimit' => null, 'enforceCounterLimit' => false,
        'counterTemplate' => null, 'counterLocale' => null,
        'clearValue' => '', 'clearLabel' => null, 'clearConfirmation' => false,
        'clearConfirmationLabel' => null, 'selectOnFocus' => false, 'caretOnFocus' => 'preserve',
        'enterKeyHint' => null, 'textDirection' => null, 'copyFeedback' => 'icon',
        'copyMode' => 'raw', 'copyTransform' => 'none', 'copyErrorLabel' => null,
        'revealMode' => 'toggle', 'concealOnBlur' => false, 'capsLockIndicator' => false,
        'revealTimeout' => null, 'passwordRequirements' => [], 'passwordRequirementsVisible' => true,
        'passwordStrengthAlgorithm' => 'balanced', 'toolsOrder' => ['emoji', 'dictation', 'icon', 'clear', 'actions'],
        'toolsVisibility' => 'always', 'emojiCloseOnSelect' => true,
        'emojiPickerPlacement' => 'auto', 'emojiPickerWidth' => 304,
        'speechDictationMode' => 'append', 'speechDictationContinuous' => true,
        'speechDictationInterim' => false, 'speechDictationTimeout' => 60000, 'speechDictationMessages' => [],
        'normalizeOnBlur' => 'none', 'pasteMode' => 'preserve', 'unicodeNormalization' => 'none',
        'readingTime' => false, 'readingWordsPerMinute' => 200,
        'suggestionLimit' => 8, 'suggestionMinLength' => 1, 'suggestionPlacement' => 'auto',
        'suggestionDescriptionPosition' => 'below',
        'suggestionDebounce' => 200, 'verificationDebounce' => 400, 'verificationMinLength' => 1, 'draftKey' => null, 'draftStorage' => 'session', 'draftLifetime' => 86400,
    ];

    public const array CHOICES = [
        'counterMode' => ['characters', 'graphemes', 'words', 'bytes'],
        'counterDisplay' => ['used', 'remaining', 'both'], 'caretOnFocus' => ['preserve', 'start', 'end'],
        'copyFeedback' => ['icon', 'message', 'both'], 'copyMode' => ['raw', 'selection', 'trimmed'],
        'copyTransform' => ['none', 'uppercase', 'lowercase'], 'revealMode' => ['toggle', 'hold'],
        'textDirection' => [null, 'auto', 'ltr', 'rtl'],
        'enterKeyHint' => [null, 'enter', 'done', 'go', 'next', 'previous', 'search', 'send'],
        'emojiPickerPlacement' => ['auto', 'top', 'bottom'], 'suggestionPlacement' => ['auto', 'top', 'bottom'],
        'suggestionDescriptionPosition' => ['below', 'trailing'],
        'speechDictationMode' => ['append', 'replace-selection'], 'toolsVisibility' => ['always', 'focus', 'hover'],
        'passwordStrengthAlgorithm' => ['balanced', 'requirements'],
        'normalizeOnBlur' => ['none', 'trim', 'lowercase', 'uppercase', 'collapse-whitespace'],
        'pasteMode' => ['preserve', 'plain-line', 'trim'], 'unicodeNormalization' => ['none', 'NFC', 'NFKC'],
        'draftStorage' => ['session', 'local'],
    ];

    /**
     * Only declared schema keys become fluent options. No method names come from saved data.
     *
     * @param  array<string, mixed>  $configuration
     * @return array<string, mixed>
     */
    public static function schemaOverrides(array $configuration): array
    {
        $options = [];
        foreach (self::schemaKeys() as $method => $key) {
            if (array_key_exists($key, $configuration)) {
                $options[$method] = $configuration[$key];
            }
        }

        return $options;
    }

    /** @return array<string, mixed> */
    public static function schemaDefaults(): array
    {
        $defaults = [];
        foreach (self::schemaKeys() as $method => $key) {
            $defaults[$key] = self::DEFAULTS[$method];
        }

        return $defaults;
    }

    /**
     * Pure declaration metadata only: never cache resolved labels or request configuration.
     *
     * @return array<string, string>
     */
    private static function schemaKeys(): array
    {
        static $keys = null;
        if ($keys === null) {
            $keys = [];
            foreach (array_keys(self::DEFAULTS) as $method) {
                $keys[$method] = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $method));
            }
        }

        return $keys;
    }

    /** @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public static function resolve(array $values): array
    {
        foreach ($values as $name => $_) {
            if (! array_key_exists($name, self::DEFAULTS)) {
                throw new InvalidArgumentException("Unknown FlexTextInput option [{$name}].");
            }
        }
        $options = array_replace(self::DEFAULTS, $values);
        foreach (self::CHOICES as $name => $choices) {
            if (! in_array($options[$name], $choices, true)) {
                throw new InvalidArgumentException("Unsupported FlexTextInput option [{$name}].");
            }
        }
        foreach (self::DEFAULTS as $name => $default) {
            if (is_bool($default) && ! is_bool($options[$name])) {
                throw new InvalidArgumentException("FlexTextInput option [{$name}] must resolve to boolean.");
            }
        }
        foreach (['softLimit' => [1, PHP_INT_MAX], 'counterLimit' => [1, PHP_INT_MAX],
            'emojiPickerWidth' => [240, 640], 'revealTimeout' => [500, 120000],
            'speechDictationTimeout' => [1000, 120000], 'readingWordsPerMinute' => [50, 1000],
            'suggestionLimit' => [1, 20], 'suggestionMinLength' => [0, 100],
            'suggestionDebounce' => [0, 2000], 'verificationDebounce' => [0, 2000], 'verificationMinLength' => [0, 100], 'draftLifetime' => [60, 2592000]] as $name => [$min, $max]) {
            $value = $options[$name];
            if ($value === null && in_array($name, ['softLimit', 'counterLimit', 'revealTimeout'], true)) {
                continue;
            }
            if (! is_int($value) || $value < $min || $value > $max) {
                throw new InvalidArgumentException("FlexTextInput option [{$name}] is outside its integer budget.");
            }
        }
        if (! is_numeric($options['counterWarningAt']) || ! is_finite((float) $options['counterWarningAt']) || $options['counterWarningAt'] < 0 || $options['counterWarningAt'] > 1) {
            throw new InvalidArgumentException('Counter warning threshold must be between 0 and 1.');
        }
        foreach (['clearLabel', 'clearConfirmationLabel', 'copyErrorLabel', 'counterTemplate', 'counterLocale', 'draftKey'] as $name) {
            if ($options[$name] !== null && (! is_string($options[$name]) || mb_strlen($options[$name]) > 512)) {
                throw new InvalidArgumentException("Invalid FlexTextInput text option [{$name}].");
            }
        }
        if ($options['clearValue'] !== null && ! is_string($options['clearValue'])) {
            throw new InvalidArgumentException('Clear value must resolve to string or null.');
        }
        if ($options['counterLocale'] !== null && ! preg_match('/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/D', $options['counterLocale'])) {
            throw new InvalidArgumentException('Counter locale must be a valid language tag.');
        }
        if ($options['draftKey'] !== null && (trim($options['draftKey']) === '' || strlen($options['draftKey']) > 120)) {
            throw new InvalidArgumentException('Draft key must be nonempty and at most 120 bytes.');
        }
        $order = $options['toolsOrder'];
        if (! is_array($order) || ! array_is_list($order) || array_filter($order, fn ($item): bool => ! is_string($item)) !== [] || count(array_unique($order)) !== count($order)
            || array_diff($order, self::DEFAULTS['toolsOrder']) !== []) {
            throw new InvalidArgumentException('Tools order must contain unique registered tool names.');
        }
        $options['toolsOrder'] = array_values(array_unique([...$order, ...self::DEFAULTS['toolsOrder']]));
        PasswordPolicy::validateRequirements($options['passwordRequirements']);
        if (! is_array($options['speechDictationMessages']) || count($options['speechDictationMessages']) > 20) {
            throw new InvalidArgumentException('Invalid speech messages.');
        }
        foreach ($options['speechDictationMessages'] as $key => $message) {
            if (! is_string($key) || ! is_string($message) || mb_strlen($message) > 512) {
                throw new InvalidArgumentException('Speech messages must be bounded strings.');
            }
        }
        if ($options['enforceCounterLimit'] && $options['counterLimit'] === null) {
            throw new InvalidArgumentException('A hard counter limit requires counterLimit().');
        }
        $options['clearLabel'] ??= TextInputMessages::get('clear');
        $options['clearConfirmationLabel'] ??= TextInputMessages::get('clear_confirm');
        $options['copyErrorLabel'] ??= TextInputMessages::get('copy_error');

        return $options;
    }
}
