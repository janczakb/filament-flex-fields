<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ScheduleField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class ScheduleFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof ScheduleField);

        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.schedule_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.schedule_variant', 'primary'));

        if (array_key_exists('days', $config) && is_array($config['days'])) {
            $field->days($config['days']);
        }

        if (array_key_exists('timezone', $config)) {
            $field->timezone($config['timezone']);
        }

        if (array_key_exists('time_step', $config)) {
            $field->timeStep((int) $config['time_step']);
        }

        if (array_key_exists('min_slots', $config)) {
            $field->minSlots((int) $config['min_slots']);
        }

        if (array_key_exists('max_slots', $config)) {
            $field->maxSlots((int) $config['max_slots']);
        }

        if (array_key_exists('allow_copy_to_weekdays', $config)) {
            $field->allowCopyToWeekdays((bool) $config['allow_copy_to_weekdays']);
        }

        if (array_key_exists('copy_source_day', $config) && filled($config['copy_source_day'])) {
            $field->copySourceDay((string) $config['copy_source_day']);
        }

        if (array_key_exists('workdays', $config) && is_array($config['workdays'])) {
            $field->workdays($config['workdays']);
        }

        if (array_key_exists('locked_days', $config) && is_array($config['locked_days'])) {
            $field->lockedDays($config['locked_days']);
        }

        if (array_key_exists('require_slots_for_enabled_days', $config)) {
            $field->requireSlotsForEnabledDays((bool) $config['require_slots_for_enabled_days']);
        }

        return $field;
    }
}
