<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDatePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDurationField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMonthPicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexYearPicker;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\DateTimeFieldConfigurator;
use Filament\Schemas\Components\Component;

final class DateTimeFieldTypeHandler extends AbstractFieldTypeHandler
{
    public function __construct(
        private readonly DateTimeFieldConfigurator $dateTime = new DateTimeFieldConfigurator,
    ) {}

    protected function supportedTypesList(): array
    {
        return [
            FieldType::Date,
            FieldType::Time,
            FieldType::DateTime,
            FieldType::DateRange,
            FieldType::Duration,
            FieldType::TimeRange,
            FieldType::Month,
            FieldType::Year,
        ];
    }

    public function make(FlexFieldDefinition $definition, string $statePath): Component
    {
        $config = $definition->config;

        return match ($definition->type) {
            FieldType::Date => $this->dateTime->configure(FlexDatePicker::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::Time => $this->dateTime->configure(FlexTimeField::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::DateTime => $this->dateTime->configure(FlexDateTimePicker::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::DateRange => $this->dateTime->configure(FlexDateRangeField::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::Duration => $this->dateTime->configure(FlexDurationField::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::TimeRange => $this->dateTime->configure(FlexTimeRangeField::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::Month => $this->dateTime->configure(FlexMonthPicker::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::Year => $this->dateTime->configure(FlexYearPicker::make($statePath)->withRecommendedDefaults(), $config),
            default => throw new \InvalidArgumentException("Unsupported field type [{$definition->type->value}] for date-time handler."),
        };
    }
}
