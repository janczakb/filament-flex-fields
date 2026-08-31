<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

/**
 * Whisper ASR language options aligned with Xenova/whisper-web (ISO 639-1 codes).
 *
 * @see https://github.com/xenova/whisper-web
 */
final class WhisperLanguageCatalog
{
    /**
     * @return list<array{code: ?string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['code' => null, 'label' => 'Auto detect'],
            ...collect(self::LANGUAGE_LABELS)
                ->map(fn (string $label, string $code): array => ['code' => $code, 'label' => $label])
                ->values()
                ->all(),
        ];
    }

    /** @var array<string, string> */
    private const LANGUAGE_LABELS = [
        'en' => 'English',
        'zh' => 'Chinese',
        'de' => 'German',
        'es' => 'Spanish/Castilian',
        'ru' => 'Russian',
        'ko' => 'Korean',
        'fr' => 'French',
        'ja' => 'Japanese',
        'pt' => 'Portuguese',
        'tr' => 'Turkish',
        'pl' => 'Polish',
        'ca' => 'Catalan/Valencian',
        'nl' => 'Dutch/Flemish',
        'ar' => 'Arabic',
        'sv' => 'Swedish',
        'it' => 'Italian',
        'id' => 'Indonesian',
        'hi' => 'Hindi',
        'fi' => 'Finnish',
        'vi' => 'Vietnamese',
        'he' => 'Hebrew',
        'uk' => 'Ukrainian',
        'el' => 'Greek',
        'ms' => 'Malay',
        'cs' => 'Czech',
        'ro' => 'Romanian/Moldavian/Moldovan',
        'da' => 'Danish',
        'hu' => 'Hungarian',
        'ta' => 'Tamil',
        'no' => 'Norwegian',
        'th' => 'Thai',
        'ur' => 'Urdu',
        'hr' => 'Croatian',
        'bg' => 'Bulgarian',
        'lt' => 'Lithuanian',
        'la' => 'Latin',
        'mi' => 'Maori',
        'ml' => 'Malayalam',
        'cy' => 'Welsh',
        'sk' => 'Slovak',
        'te' => 'Telugu',
        'fa' => 'Persian',
        'lv' => 'Latvian',
        'bn' => 'Bengali',
        'sr' => 'Serbian',
        'az' => 'Azerbaijani',
        'sl' => 'Slovenian',
        'kn' => 'Kannada',
        'et' => 'Estonian',
        'mk' => 'Macedonian',
        'br' => 'Breton',
        'eu' => 'Basque',
        'is' => 'Icelandic',
        'hy' => 'Armenian',
        'ne' => 'Nepali',
        'mn' => 'Mongolian',
        'bs' => 'Bosnian',
        'kk' => 'Kazakh',
        'sq' => 'Albanian',
        'sw' => 'Swahili',
        'gl' => 'Galician',
        'mr' => 'Marathi',
        'pa' => 'Punjabi/Panjabi',
        'si' => 'Sinhala/Sinhalese',
        'km' => 'Khmer',
        'sn' => 'Shona',
        'yo' => 'Yoruba',
        'so' => 'Somali',
        'af' => 'Afrikaans',
        'oc' => 'Occitan',
        'ka' => 'Georgian',
        'be' => 'Belarusian',
        'tg' => 'Tajik',
        'sd' => 'Sindhi',
        'gu' => 'Gujarati',
        'am' => 'Amharic',
        'yi' => 'Yiddish',
        'lo' => 'Lao',
        'uz' => 'Uzbek',
        'fo' => 'Faroese',
        'ht' => 'Haitian Creole/Haitian',
        'ps' => 'Pashto/Pushto',
        'tk' => 'Turkmen',
        'nn' => 'Nynorsk',
        'mt' => 'Maltese',
        'sa' => 'Sanskrit',
        'lb' => 'Luxembourgish/Letzeburgesch',
        'my' => 'Myanmar/Burmese',
        'bo' => 'Tibetan',
        'tl' => 'Tagalog',
        'mg' => 'Malagasy',
        'as' => 'Assamese',
        'tt' => 'Tatar',
        'haw' => 'Hawaiian',
        'ln' => 'Lingala',
        'ha' => 'Hausa',
        'ba' => 'Bashkir',
        'jw' => 'Javanese',
        'su' => 'Sundanese',
    ];
}
