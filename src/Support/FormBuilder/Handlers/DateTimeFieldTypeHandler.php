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
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeSegmentsField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexYearPicker;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\DateTimeFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexTimeSegmentsFieldConfigurator;
use Filament\Schemas\Components\Component;

final class DateTimeFieldTypeHandler extends AbstractFieldTypeHandler
{
    public function __construct(
        private readonly DateTimeFieldConfigurator $dateTime = new DateTimeFieldConfigurator,
        private readonly FlexTimeSegmentsFieldConfigurator $timeSegments = new FlexTimeSegmentsFieldConfigurator,
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
            FieldType::Time => $this->makeTimeField($statePath, $config),
            FieldType::DateTime => $this->dateTime->configure(FlexDateTimePicker::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::DateRange => $this->dateTime->configure(FlexDateRangeField::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::Duration => $this->dateTime->configure(FlexDurationField::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::TimeRange => $this->dateTime->configure(FlexTimeRangeField::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::Month => $this->dateTime->configure(FlexMonthPicker::make($statePath)->withRecommendedDefaults(), $config),
            FieldType::Year => $this->dateTime->configure(FlexYearPicker::make($statePath)->withRecommendedDefaults(), $config),
            default => throw new \InvalidArgumentException("Unsupported field type [{$definition->type->value}] for date-time handler."),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function makeTimeField(string $statePath, array $config): Component
    {
        $picker = (string) ($config['time_picker'] ?? 'segmented');

        if (in_array($picker, ['dropdown', 'segments'], true)) {
            return $this->timeSegments->configure(
                FlexTimeSegmentsField::make($statePath)->withRecommendedDefaults(),
                $config,
            );
        }

        return $this->dateTime->configure(
            FlexTimeField::make($statePath)->withRecommendedDefaults(),
            $config,
        );
    }
}
