<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use InvalidArgumentException;
use NumberFormatter;

class CurrencyCountries
{
    /**
     * @var array<string, array{symbol: string, name: string, decimals: int, locale: string}>|null
     */
    protected static ?array $resolvedDefinitions = null;

    /**
     * @var array<string, mixed>|null
     */
    protected static ?array $cachedConfig = null;

    /**
     * @var array<string, string>
     */
    protected static array $decimalSeparators = [];

    /**
     * @var array<string, string>
     */
    protected static array $thousandsSeparators = [];

    /**
     * @var array<string, array{symbol: string, name: string, decimals: int, locale: string}>
     */
    private const CURRENCIES = [
        'AED' => [
            'symbol' => 'AED',
            'name' => 'UAE dirham',
            'decimals' => 2,
            'locale' => 'ar-AE',
        ],
        'ARS' => [
            'symbol' => 'AR$',
            'name' => 'Argentine peso',
            'decimals' => 2,
            'locale' => 'es-AR',
        ],
        'AUD' => [
            'symbol' => 'A$',
            'name' => 'Australian dollar',
            'decimals' => 2,
            'locale' => 'en-AU',
        ],
        'BGN' => [
            'symbol' => 'лв',
            'name' => 'Bulgarian lev',
            'decimals' => 2,
            'locale' => 'bg-BG',
        ],
        'BRL' => [
            'symbol' => 'R$',
            'name' => 'Brazilian real',
            'decimals' => 2,
            'locale' => 'pt-BR',
        ],
        'CAD' => [
            'symbol' => 'CA$',
            'name' => 'Canadian dollar',
            'decimals' => 2,
            'locale' => 'en-CA',
        ],
        'CHF' => [
            'symbol' => 'CHF',
            'name' => 'Swiss franc',
            'decimals' => 2,
            'locale' => 'de-CH',
        ],
        'CNY' => [
            'symbol' => 'CN¥',
            'name' => 'Chinese yuan',
            'decimals' => 2,
            'locale' => 'zh-CN',
        ],
        'CZK' => [
            'symbol' => 'Kč',
            'name' => 'Czech koruna',
            'decimals' => 2,
            'locale' => 'cs-CZ',
        ],
        'DKK' => [
            'symbol' => 'kr',
            'name' => 'Danish krone',
            'decimals' => 2,
            'locale' => 'da-DK',
        ],
        'EGP' => [
            'symbol' => 'E£',
            'name' => 'Egyptian pound',
            'decimals' => 2,
            'locale' => 'ar-EG',
        ],
        'EUR' => [
            'symbol' => '€',
            'name' => 'Euro',
            'decimals' => 2,
            'locale' => 'de-DE',
        ],
        'GBP' => [
            'symbol' => '£',
            'name' => 'British pound',
            'decimals' => 2,
            'locale' => 'en-GB',
        ],
        'HKD' => [
            'symbol' => 'HK$',
            'name' => 'Hong Kong dollar',
            'decimals' => 2,
            'locale' => 'zh-HK',
        ],
        'HUF' => [
            'symbol' => 'Ft',
            'name' => 'Hungarian forint',
            'decimals' => 0,
            'locale' => 'hu-HU',
        ],
        'IDR' => [
            'symbol' => 'Rp',
            'name' => 'Indonesian rupiah',
            'decimals' => 0,
            'locale' => 'id-ID',
        ],
        'ILS' => [
            'symbol' => '₪',
            'name' => 'Israeli shekel',
            'decimals' => 2,
            'locale' => 'he-IL',
        ],
        'INR' => [
            'symbol' => '₹',
            'name' => 'Indian rupee',
            'decimals' => 2,
            'locale' => 'en-IN',
        ],
        'JPY' => [
            'symbol' => '¥',
            'name' => 'Japanese yen',
            'decimals' => 0,
            'locale' => 'ja-JP',
        ],
        'KRW' => [
            'symbol' => '₩',
            'name' => 'South Korean won',
            'decimals' => 0,
            'locale' => 'ko-KR',
        ],
        'MXN' => [
            'symbol' => 'MX$',
            'name' => 'Mexican peso',
            'decimals' => 2,
            'locale' => 'es-MX',
        ],
        'MYR' => [
            'symbol' => 'RM',
            'name' => 'Malaysian ringgit',
            'decimals' => 2,
            'locale' => 'ms-MY',
        ],
        'NOK' => [
            'symbol' => 'kr',
            'name' => 'Norwegian krone',
            'decimals' => 2,
            'locale' => 'nb-NO',
        ],
        'NZD' => [
            'symbol' => 'NZ$',
            'name' => 'New Zealand dollar',
            'decimals' => 2,
            'locale' => 'en-NZ',
        ],
        'PHP' => [
            'symbol' => '₱',
            'name' => 'Philippine peso',
            'decimals' => 2,
            'locale' => 'en-PH',
        ],
        'PLN' => [
            'symbol' => 'zł',
            'name' => 'Polish złoty',
            'decimals' => 2,
            'locale' => 'pl-PL',
        ],
        'RON' => [
            'symbol' => 'lei',
            'name' => 'Romanian leu',
            'decimals' => 2,
            'locale' => 'ro-RO',
        ],
        'SAR' => [
            'symbol' => 'SAR',
            'name' => 'Saudi riyal',
            'decimals' => 2,
            'locale' => 'ar-SA',
        ],
        'SEK' => [
            'symbol' => 'kr',
            'name' => 'Swedish krona',
            'decimals' => 2,
            'locale' => 'sv-SE',
        ],
        'SGD' => [
            'symbol' => 'S$',
            'name' => 'Singapore dollar',
            'decimals' => 2,
            'locale' => 'en-SG',
        ],
        'THB' => [
            'symbol' => '฿',
            'name' => 'Thai baht',
            'decimals' => 2,
            'locale' => 'th-TH',
        ],
        'TRY' => [
            'symbol' => '₺',
            'name' => 'Turkish lira',
            'decimals' => 2,
            'locale' => 'tr-TR',
        ],
        'UAH' => [
            'symbol' => '₴',
            'name' => 'Ukrainian hryvnia',
            'decimals' => 2,
            'locale' => 'uk-UA',
        ],
        'USD' => [
            'symbol' => '$',
            'name' => 'US dollar',
            'decimals' => 2,
            'locale' => 'en-US',
        ],
        'ZAR' => [
            'symbol' => 'R',
            'name' => 'South African rand',
            'decimals' => 2,
            'locale' => 'en-ZA',
        ],
    ];

    /**
     * @return array<string, array{symbol: string, name: string, decimals: int, locale: string}>
     */
    public static function definitions(): array
    {
        $currentConfig = config('filament-flex-fields.currencies', []);

        if (self::$resolvedDefinitions === null || self::$cachedConfig !== $currentConfig) {
            self::$cachedConfig = $currentConfig;
            self::$resolvedDefinitions = array_merge(
                self::CURRENCIES,
                self::configuredCurrencies(),
            );
        }

        return self::$resolvedDefinitions;
    }

    /**
     * @return list<string>
     */
    public static function allSupportedCodes(): array
    {
        $codes = array_keys(self::definitions());
        sort($codes);

        return $codes;
    }

    public static function isSupported(string $currencyCode): bool
    {
        return isset(self::definitions()[strtoupper($currencyCode)]);
    }

    /**
     * @return array<string, array{symbol: string, name: string, decimals: int, locale: string}>
     */
    private static function configuredCurrencies(): array
    {
        $configured = config('filament-flex-fields.currencies', []);

        if (! is_array($configured) || $configured === []) {
            return [];
        }

        $normalized = [];

        foreach ($configured as $code => $definition) {
            if (! is_string($code) || $code === '' || ! is_array($definition)) {
                continue;
            }

            $normalized[strtoupper($code)] = self::normalizeConfiguredDefinition($definition, strtoupper($code));
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array{symbol: string, name: string, decimals: int, locale: string}
     */
    private static function normalizeConfiguredDefinition(array $definition, string $code): array
    {
        foreach (['symbol', 'name', 'locale'] as $key) {
            if (! isset($definition[$key]) || ! is_string($definition[$key]) || trim($definition[$key]) === '') {
                throw new InvalidArgumentException("Currency [{$code}] config is missing a valid [{$key}].");
            }
        }

        if (! array_key_exists('decimals', $definition) || ! is_numeric($definition['decimals'])) {
            throw new InvalidArgumentException("Currency [{$code}] config must include numeric [decimals].");
        }

        $decimals = (int) $definition['decimals'];

        if ($decimals < 0 || $decimals > 4) {
            throw new InvalidArgumentException("Currency [{$code}] decimals must be between 0 and 4.");
        }

        return [
            'symbol' => (string) $definition['symbol'],
            'name' => (string) $definition['name'],
            'decimals' => $decimals,
            'locale' => (string) $definition['locale'],
        ];
    }

    public static function symbol(string $currencyCode): string
    {
        return self::definition($currencyCode)['symbol'];
    }

    public static function name(string $currencyCode): string
    {
        $code = strtoupper($currencyCode);
        $key = "filament-flex-fields::currencies.{$code}";
        $translation = __($key);

        if ($translation !== $key) {
            return (string) $translation;
        }

        return self::definition($currencyCode)['name'];
    }

    public static function decimals(string $currencyCode): int
    {
        return self::definition($currencyCode)['decimals'];
    }

    public static function locale(string $currencyCode): string
    {
        return self::definition($currencyCode)['locale'];
    }

    /**
     * @param  list<string>|null  $only
     * @return list<string>
     */
    public static function resolve(?array $only = null): array
    {
        $supported = array_flip(self::allSupportedCodes());

        if ($only === null) {
            return self::allSupportedCodes();
        }

        $codes = [];

        foreach ($only as $code) {
            $normalized = strtoupper((string) $code);

            if (! isset($supported[$normalized])) {
                continue;
            }

            $codes[] = $normalized;
        }

        sort($codes);

        return array_values(array_unique($codes));
    }

    /**
     * @param  list<string>|null  $only
     * @return list<array{code: string, symbol: string, name: string, decimals: int, locale: string}>
     */
    public static function metadata(?array $only = null): array
    {
        return array_map(
            fn (string $code): array => [
                'code' => $code,
                'symbol' => self::symbol($code),
                'name' => self::name($code),
                'decimals' => self::decimals($code),
                'locale' => self::locale($code),
            ],
            self::resolve($only),
        );
    }

    /**
     * @param  list<string>|null  $only
     * @return array<string, array{label: string, description: string}>
     */
    public static function selectOptions(?array $only = null): array
    {
        $options = [];

        foreach (self::metadata($only) as $currency) {
            $options[$currency['code']] = [
                'label' => $currency['name'],
                'description' => $currency['code'].' · '.$currency['symbol'],
            ];
        }

        return $options;
    }

    public static function toMinorUnits(float|int|string|null $major, string $currencyCode): ?int
    {
        if ($major === null || $major === '') {
            return null;
        }

        $decimals = self::decimals($currencyCode);
        $factor = 10 ** $decimals;

        return (int) round(((float) $major) * $factor);
    }

    public static function toMajorUnits(?int $minor, string $currencyCode): ?float
    {
        if ($minor === null) {
            return null;
        }

        $decimals = self::decimals($currencyCode);
        $factor = 10 ** $decimals;

        return $minor / $factor;
    }

    public static function formatMajor(float|int|null $major, string $currencyCode, ?string $locale = null): string
    {
        if ($major === null) {
            return '';
        }

        $decimals = self::decimals($currencyCode);
        $locale = $locale ?? self::locale($currencyCode);

        return number_format(
            (float) $major,
            $decimals,
            self::decimalSeparator($locale),
            self::thousandsSeparator($locale),
        );
    }

    public static function decimalSeparator(string $locale): string
    {
        if (isset(self::$decimalSeparators[$locale])) {
            return self::$decimalSeparators[$locale];
        }

        $formatted = (new NumberFormatter($locale, NumberFormatter::DECIMAL))->format(1.1);

        if (! is_string($formatted)) {
            return self::$decimalSeparators[$locale] = '.';
        }

        return self::$decimalSeparators[$locale] = str_replace(['1', '0'], '', $formatted) ?: '.';
    }

    public static function thousandsSeparator(string $locale): string
    {
        if (isset(self::$thousandsSeparators[$locale])) {
            return self::$thousandsSeparators[$locale];
        }

        $formatted = (new NumberFormatter($locale, NumberFormatter::DECIMAL))->format(1000);

        if (! is_string($formatted)) {
            return self::$thousandsSeparators[$locale] = ',';
        }

        return self::$thousandsSeparators[$locale] = str_replace(['1', '0'], '', $formatted) ?: ',';
    }

    /**
     * @return array{wholeDigits: string, fracDigits: string, inDecimal: bool, negative: bool}
     */
    public static function editStateFromMinor(?int $minor, int $decimals): array
    {
        if ($minor === null) {
            return [
                'wholeDigits' => '',
                'fracDigits' => '',
                'inDecimal' => false,
                'negative' => false,
            ];
        }

        $negative = $minor < 0;
        $value = abs($minor);

        if ($decimals === 0) {
            return [
                'wholeDigits' => (string) $value,
                'fracDigits' => '',
                'inDecimal' => false,
                'negative' => $negative,
            ];
        }

        $factor = 10 ** $decimals;
        $whole = intdiv($value, $factor);
        $fracValue = $value % $factor;
        $fracDigits = $fracValue === 0
            ? ''
            : rtrim(str_pad((string) $fracValue, $decimals, '0', STR_PAD_LEFT), '0');

        return [
            'wholeDigits' => $whole === 0 && $fracDigits !== '' ? '0' : (string) $whole,
            'fracDigits' => $fracDigits,
            'inDecimal' => false,
            'negative' => $negative,
        ];
    }

    /**
     * @param  array{wholeDigits: string, fracDigits: string, inDecimal: bool, negative: bool}  $edit
     */
    public static function minorFromEditState(array $edit, int $decimals): ?int
    {
        $whole = preg_replace('/\D/', '', $edit['wholeDigits'] ?? '') ?? '';
        $frac = preg_replace('/\D/', '', $edit['fracDigits'] ?? '') ?? '';

        if ($whole === '' && $frac === '') {
            return null;
        }

        $paddedFrac = $decimals === 0
            ? ''
            : str_pad(substr($frac, 0, $decimals), $decimals, '0', STR_PAD_RIGHT);

        $combined = $decimals === 0
            ? ($whole !== '' ? $whole : '0')
            : ($whole !== '' ? $whole : '0').$paddedFrac;

        $numeric = (int) $combined;

        return ($edit['negative'] ?? false) ? -$numeric : $numeric;
    }

    public static function groupWholeDigits(string $wholeDigits, string $groupSeparator): string
    {
        $digits = preg_replace('/\D/', '', $wholeDigits) ?? '';

        if ($digits === '') {
            return '';
        }

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', $groupSeparator, $digits) ?? $digits;
    }

    /**
     * @param  array{wholeDigits: string, fracDigits: string, inDecimal: bool, negative: bool}  $edit
     * @return list<array{type: string, char: string, key: string, ghost: bool}>
     */
    public static function buildDisplaySegments(array $edit, string $locale, int $decimals, bool $showGhost = false): array
    {
        $groupSeparator = self::thousandsSeparator($locale);
        $decimalSeparator = self::decimalSeparator($locale);
        $segments = [];
        $wholeDigitIndex = 0;
        $wholeGrouped = self::groupWholeDigits($edit['wholeDigits'], $groupSeparator);

        foreach (mb_str_split($wholeGrouped) as $char) {
            if ($char === $groupSeparator) {
                $segments[] = [
                    'type' => 'separator',
                    'char' => $char,
                    'key' => "sep-g-{$wholeDigitIndex}",
                    'ghost' => false,
                ];
            } else {
                $segments[] = [
                    'type' => 'digit',
                    'char' => $char,
                    'key' => "w-{$wholeDigitIndex}",
                    'ghost' => false,
                ];
                $wholeDigitIndex++;
            }
        }

        if ($decimals === 0) {
            return $segments;
        }

        if ($edit['inDecimal'] || $edit['fracDigits'] !== '') {
            $segments[] = [
                'type' => 'separator',
                'char' => $decimalSeparator,
                'key' => 'sep-decimal',
                'ghost' => false,
            ];

            $fracDigits = (string) ($edit['fracDigits'] ?? '');

            for ($index = 0; $index < strlen($fracDigits); $index++) {
                $segments[] = [
                    'type' => 'digit',
                    'char' => $fracDigits[$index],
                    'key' => "f-{$index}",
                    'ghost' => false,
                ];
            }

            if ($showGhost) {
                $ghostCount = max(0, $decimals - strlen($fracDigits));

                for ($index = 0; $index < $ghostCount; $index++) {
                    $segments[] = [
                        'type' => 'digit',
                        'char' => '0',
                        'key' => "g-{$index}",
                        'ghost' => true,
                    ];
                }
            }
        }

        return $segments;
    }

    /**
     * @return array{symbol: string, name: string, decimals: int, locale: string}
     */
    private static function definition(string $currencyCode): array
    {
        $code = strtoupper($currencyCode);

        $definitions = self::definitions();

        if (! isset($definitions[$code])) {
            throw new InvalidArgumentException("Currency [{$code}] is not supported.");
        }

        return $definitions[$code];
    }
}
