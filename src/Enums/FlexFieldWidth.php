<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum FlexFieldWidth: string
{
    case Full = 'full';
    case ThreeQuarters = 'three_quarters';
    case TwoThirds = 'two_thirds';
    case Half = 'half';
    case Third = 'third';
    case Quarter = 'quarter';

    public function columnSpan(): int
    {
        return match ($this) {
            self::Full => 12,
            self::ThreeQuarters => 9,
            self::TwoThirds => 8,
            self::Half => 6,
            self::Third => 4,
            self::Quarter => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Full => '100%',
            self::ThreeQuarters => '75%',
            self::TwoThirds => '66%',
            self::Half => '50%',
            self::Third => '33%',
            self::Quarter => '25%',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
