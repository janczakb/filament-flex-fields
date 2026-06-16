<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ScheduleField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ScheduleFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'schedule__bare' => ScheduleField::defaultSchedule('Europe/Warsaw'),
            'schedule__default' => ScheduleField::defaultSchedule('Europe/Warsaw'),
            'schedule__weekdays_only' => ScheduleField::defaultSchedule('UTC', ['mon', 'tue', 'wed', 'thu', 'fri']),
            'schedule__locked_weekends' => ScheduleField::defaultSchedule('Europe/Warsaw'),
            'schedule__no_timezone' => [
                'days' => ScheduleField::defaultSchedule(null)['days'],
            ],
            'schedule__sm' => ScheduleField::defaultSchedule('America/New_York'),
            'schedule__lg' => ScheduleField::defaultSchedule('Asia/Tokyo'),
            'schedule__secondary' => ScheduleField::defaultSchedule('Europe/London'),
            'schedule__soft' => ScheduleField::defaultSchedule('Australia/Sydney'),
            'schedule__disabled' => ScheduleField::defaultSchedule('UTC'),
            'schedule__read_only' => ScheduleField::defaultSchedule('UTC'),
            'schedule__custom_slots' => [
                'timezone' => 'Europe/Warsaw',
                'days' => [
                    'mon' => [
                        'enabled' => true,
                        'slots' => [
                            ['from' => '09:00', 'to' => '12:00', 'type' => 'slot'],
                            ['from' => '12:00', 'to' => '13:00', 'type' => 'break'],
                            ['from' => '13:00', 'to' => '17:00', 'type' => 'slot'],
                        ],
                    ],
                    'tue' => ['enabled' => true, 'slots' => [['from' => '10:00', 'to' => '18:00']]],
                    'wed' => ['enabled' => true, 'slots' => [['from' => '10:00', 'to' => '18:00']]],
                    'thu' => ['enabled' => true, 'slots' => [['from' => '10:00', 'to' => '18:00']]],
                    'fri' => ['enabled' => true, 'slots' => [['from' => '10:00', 'to' => '16:00']]],
                    'sat' => ['enabled' => false, 'slots' => []],
                    'sun' => ['enabled' => false, 'slots' => []],
                ],
            ],
        ];
    }

    public function section(): Section
    {
        return Section::make('Schedule field')
            ->description('Weekly availability editor with day toggles, time slots, breaks, copy-to-weekdays, and optional timezone.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Section::make('Default')
                    ->compact()
                    ->schema([
                        $this->field('schedule__default', 'Business hours')
                            ->workdays(['mon', 'tue', 'wed', 'thu', 'fri'])
                            ->helperText('Mon–Fri 09:00–17:00. Copy Monday to configured workdays. 5-minute steps. Open days allow 1–6 slots.'),
                    ]),
                Section::make('Variants')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->extraAttributes(['class' => 'fff-playground-variants'])
                            ->schema([
                                $this->field('schedule__secondary', 'Secondary')->variant('secondary'),
                                $this->field('schedule__soft', 'Soft')->variant('soft'),
                            ]),
                    ]),
                Section::make('Sizes')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->extraAttributes(['class' => 'fff-playground-variants'])
                            ->schema([
                                $this->field('schedule__sm', 'Small')->size('sm'),
                                $this->field('schedule__lg', 'Large')->size('lg'),
                            ]),
                    ]),
                Section::make('Weekdays only')
                    ->compact()
                    ->schema([
                        $this->field('schedule__weekdays_only', 'Weekday schedule')
                            ->days(['mon', 'tue', 'wed', 'thu', 'fri']),
                    ]),
                Section::make('Locked weekends')
                    ->compact()
                    ->schema([
                        $this->field('schedule__locked_weekends', 'Weekends locked')
                            ->lockedDays(['sat', 'sun'])
                            ->helperText('Saturday and Sunday show a lock icon instead of a toggle switch.'),
                    ]),
                Section::make('Without timezone')
                    ->compact()
                    ->schema([
                        $this->field('schedule__no_timezone', 'Local schedule')
                            ->timezone(null),
                    ]),
                Section::make('Split shifts')
                    ->compact()
                    ->schema([
                        $this->field('schedule__custom_slots', 'Split shifts with lunch break')
                            ->helperText('Monday shows morning + afternoon slots. Use “Copy to weekdays” to propagate.'),
                    ]),
                Section::make('States')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                $this->field('schedule__disabled', 'Disabled')->disabled(),
                                $this->field('schedule__read_only', 'Read only')->readOnly(),
                            ]),
                    ]),
            ]);
    }

    protected function field(string $name, string $label): ScheduleField
    {
        return ScheduleField::make($name)
            ->label($label)
            ->timeStep(5)
            ->minSlots(1)
            ->maxSlots(6);
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            ScheduleField::make('schedule__bare')
                ->hiddenLabel()
                ->minSlots(1)
                ->maxSlots(6)
                ->columnSpanFull(),
            $this->section(),
        ];
    }
}
