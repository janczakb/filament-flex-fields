<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SwitchPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'switch__default' => true,
            'switch__secondary' => true,
            'switch__sidebar' => true,
            'switch__sm' => true,
            'switch__md' => true,
            'switch__lg' => false,
            'switch__disabled' => true,
            'switch__admin_icons' => false,
            'switch__admin_colors' => true,
            'switch__terms' => false,
            'switch__label_end' => true,
            'switch__compact' => false,
            'switch__ripple' => true,
            'switch__inline' => true,
            'switch__inline_sm' => false,
            'switch__inline_lg' => true,
            'switch__inline_icons' => false,
            'switch__inline_with_label' => true,
            'switch__inline_with_label_end' => false,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Switch')
                ->description('SaaS-style switch rows with capsule thumb, variants, groups, and announcement cards.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SwitchField::make('switch__default')
                                ->label('Animations')
                                ->variant('default'),
                            SwitchField::make('switch__secondary')
                                ->label('Animations')
                                ->variant('secondary'),
                        ]),
                    SwitchField::make('switch__sidebar')
                        ->label('Try the new sidebar')
                        ->description('Keep your pages, meetings, and AI within reach.')
                        ->badge('New')
                        ->badgeColor('primary')
                        ->layout('card'),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SwitchField::make('switch__sm')
                                ->label('Small')
                                ->size('sm'),
                            SwitchField::make('switch__md')
                                ->label('Medium'),
                            SwitchField::make('switch__lg')
                                ->label('Large')
                                ->size('lg'),
                        ]),
                    SwitchField::make('switch__disabled')
                        ->label('Disabled switch')
                        ->disabled(),
                    Grid::make(['default' => 1, 'sm' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SwitchField::make('switch__admin_icons')
                                ->label('Is admin')
                                ->onIcon(GravityIcon::Thunderbolt)
                                ->offIcon(GravityIcon::Person),
                            SwitchField::make('switch__admin_colors')
                                ->label('Is admin')
                                ->onColor('success')
                                ->offColor('danger'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SwitchField::make('switch__label_end')
                                ->label('Switch first')
                                ->labelPosition('end'),
                            SwitchField::make('switch__compact')
                                ->label('Compact row')
                                ->compact(),
                            SwitchField::make('switch__ripple')
                                ->label('Ripple feedback')
                                ->ripple(),
                        ]),
                    Section::make('Inline')
                        ->compact()
                        ->description('Bare switch without the track box. Use inline() for switch only, or inlineWithLabel() for switch + Filament label.')
                        ->schema([
                            Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                                ->extraAttributes(['class' => 'fff-playground-variants'])
                                ->schema([
                                    SwitchField::make('switch__inline')
                                        ->label('Switch only')
                                        ->inline(),
                                    SwitchField::make('switch__inline_sm')
                                        ->label('Switch only · sm')
                                        ->inline()
                                        ->size('sm'),
                                    SwitchField::make('switch__inline_lg')
                                        ->label('Switch only · lg')
                                        ->inline()
                                        ->size('lg'),
                                    SwitchField::make('switch__inline_icons')
                                        ->label('Switch only · icons')
                                        ->inline()
                                        ->onIcon(GravityIcon::Thunderbolt)
                                        ->offIcon(GravityIcon::Person),
                                ]),
                            Grid::make(['default' => 1, 'sm' => 2])
                                ->extraAttributes(['class' => 'fff-playground-variants'])
                                ->schema([
                                    SwitchField::make('switch__inline_with_label')
                                        ->label('Notifications')
                                        ->helperText('inlineWithLabel()')
                                        ->inlineWithLabel(),
                                    SwitchField::make('switch__inline_with_label_end')
                                        ->label('Dark mode')
                                        ->helperText('inlineWithLabel() · labelPosition(end)')
                                        ->inlineWithLabel()
                                        ->labelPosition('end'),
                                ]),
                        ]),
                    SwitchField::make('switch__terms')
                        ->label('I accept the terms of service')
                        ->accepted()
                        ->helperText('Must be enabled before saving.'),
                ]),
        ];
    }
}
