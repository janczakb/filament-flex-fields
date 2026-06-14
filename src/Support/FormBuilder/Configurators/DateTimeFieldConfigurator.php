<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimeField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Carbon\Carbon;
use Filament\Schemas\Components\Component;

final class DateTimeFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof FlexDateTimeField);

        return $this->configureDateTimeField($field, $config);
    }

    public function configureDateTimeField(FlexDateTimeField $field, array $config): FlexDateTimeField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.date_time_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.date_time_variant', 'primary'));

        if (array_key_exists('granularity', $config) && filled($config['granularity'])) {
            $field->granularity($config['granularity']);
        }

        if (array_key_exists('hour_cycle', $config)) {
            $field->hourCycle((int) $config['hour_cycle']);
        }

        if (array_key_exists('show_seconds', $config)) {
            $field->showSeconds((bool) $config['show_seconds']);
        }

        if (array_key_exists('show_year_segment', $config)) {
            $field->showYearSegment((bool) $config['show_year_segment']);
        }

        if (array_key_exists('month_display', $config) && filled($config['month_display'])) {
            $field->monthDisplay($config['month_display']);
        }

        if (array_key_exists('min_value', $config) && filled($config['min_value'])) {
            $field->minValue($config['min_value']);
        } elseif (array_key_exists('min_date', $config) && filled($config['min_date'])) {
            $field->minDate($config['min_date']);
        }

        if (array_key_exists('max_value', $config) && filled($config['max_value'])) {
            $field->maxValue($config['max_value']);
        } elseif (array_key_exists('max_date', $config) && filled($config['max_date'])) {
            $field->maxDate($config['max_date']);
        }

        if (array_key_exists('display_format', $config) && filled($config['display_format'])) {
            $field->displayFormat($config['display_format']);
        }

        if (array_key_exists('storage_format', $config) && filled($config['storage_format'])) {
            $field->storageFormat($config['storage_format']);
        }

        if (array_key_exists('locale', $config) && filled($config['locale'])) {
            $field->locale($config['locale']);
        }

        if (array_key_exists('time_zone', $config) && filled($config['time_zone'])) {
            $field->timeZone($config['time_zone']);
        }

        if (array_key_exists('force_leading_zeros', $config)) {
            $field->forceLeadingZeros((bool) $config['force_leading_zeros']);
        }

        if (array_key_exists('hide_time_zone', $config)) {
            $field->hideTimeZone((bool) $config['hide_time_zone']);
        }

        if (array_key_exists('hide_time_section', $config)) {
            $field->hideTimeSection((bool) $config['hide_time_section']);
        }

        if (array_key_exists('close_on_select', $config)) {
            $field->closeOnSelect((bool) $config['close_on_select']);
        }

        if (array_key_exists('allow_same_day', $config)) {
            $field->allowSameDay((bool) $config['allow_same_day']);
        }

        if (array_key_exists('range_separator', $config)) {
            $field->rangeSeparator($config['range_separator']);
        }

        if (array_key_exists('first_day_of_week', $config)) {
            $field->firstDayOfWeek((int) $config['first_day_of_week']);
        }

        if (array_key_exists('unavailable_dates', $config) && is_array($config['unavailable_dates'])) {
            $dates = array_values($config['unavailable_dates']);
            $field->isDateUnavailable(fn (Carbon $date): bool => in_array($date->format('Y-m-d'), $dates, true));
        }

        return $field;
    }
}
