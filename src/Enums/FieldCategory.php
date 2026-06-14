<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

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
            self::Text => __('filament-flex-fields::default.categories.text'),
            self::Number => __('filament-flex-fields::default.categories.number'),
            self::Choice => __('filament-flex-fields::default.categories.choice'),
            self::DateTime => __('filament-flex-fields::default.categories.datetime'),
            self::Media => __('filament-flex-fields::default.categories.media'),
            self::Advanced => __('filament-flex-fields::default.categories.advanced'),
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
