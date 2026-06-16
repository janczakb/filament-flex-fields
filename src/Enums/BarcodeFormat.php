<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum BarcodeFormat: string
{
    case Qr = 'qr';
    case Ean13 = 'ean_13';
    case Ean8 = 'ean_8';
    case UpcA = 'upc_a';
    case UpcE = 'upc_e';
    case Code128 = 'code_128';
    case Code39 = 'code_39';
    case Itf = 'itf';
    case Pdf417 = 'pdf417';
    case DataMatrix = 'data_matrix';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $format): string => $format->value, self::cases());
    }

    /**
     * @param  list<string|self>  $formats
     * @return list<self>
     */
    public static function normalizeList(array $formats): array
    {
        $normalized = [];

        foreach ($formats as $format) {
            if ($format instanceof self) {
                $normalized[] = $format;

                continue;
            }

            if (! is_string($format)) {
                continue;
            }

            $resolved = self::tryFrom($format);

            if ($resolved !== null) {
                $normalized[] = $resolved;
            }
        }

        return array_values(array_unique($normalized, SORT_REGULAR));
    }
}
