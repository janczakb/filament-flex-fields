<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

use Bjanczak\FilamentFlexFields\Support\Translations;

enum FieldCategory: string
{
    case Text = 'text';
    case Number = 'number';
    case Choice = 'choice';
    case DateTime = 'datetime';
    case Media = 'media';
    case Advanced = 'advanced';

    public function label(): string
    {
        return match ($this) {
            self::Text => Translations::get('filament-flex-fields::default.categories.text'),
            self::Number => Translations::get('filament-flex-fields::default.categories.number'),
            self::Choice => Translations::get('filament-flex-fields::default.categories.choice'),
            self::DateTime => Translations::get('filament-flex-fields::default.categories.datetime'),
            self::Media => Translations::get('filament-flex-fields::default.categories.media'),
            self::Advanced => Translations::get('filament-flex-fields::default.categories.advanced'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Text => 'heroicon-o-bars-3-bottom-left',
            self::Number => 'heroicon-o-calculator',
            self::Choice => 'heroicon-o-list-bullet',
            self::DateTime => 'heroicon-o-calendar-days',
            self::Media => 'heroicon-o-photo',
            self::Advanced => 'heroicon-o-code-bracket-square',
        };
    }
}
