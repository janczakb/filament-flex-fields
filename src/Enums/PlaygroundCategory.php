<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum PlaygroundCategory: string
{
    case Guides = 'guides';
    case Navigation = 'navigation';
    case Buttons = 'buttons';
    case Pickers = 'pickers';
    case DateAndTime = 'date_and_time';
    case Colors = 'colors';
    case Controls = 'controls';
    case Collections = 'collections';
    case TextInput = 'text_input';
    case DataDisplay = 'data_display';
    case Media = 'media';
    case Location = 'location';

    public function label(): string
    {
        return match ($this) {
            self::Guides => 'Guides',
            self::Navigation => 'Navigation',
            self::Buttons => 'Buttons',
            self::Pickers => 'Pickers',
            self::DateAndTime => 'Date and Time',
            self::Colors => 'Colors',
            self::Controls => 'Controls',
            self::Collections => 'Collections',
            self::TextInput => 'Text Input',
            self::DataDisplay => 'Data Display',
            self::Media => 'Media',
            self::Location => 'Location',
        };
    }

    public function sort(): int
    {
        return match ($this) {
            self::Guides => 10,
            self::Navigation => 20,
            self::Buttons => 30,
            self::Pickers => 40,
            self::DateAndTime => 50,
            self::Colors => 60,
            self::Controls => 70,
            self::Collections => 80,
            self::TextInput => 90,
            self::DataDisplay => 100,
            self::Media => 110,
            self::Location => 120,
        };
    }
}
