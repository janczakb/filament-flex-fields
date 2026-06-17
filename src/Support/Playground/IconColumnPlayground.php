<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\IconColumn;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;

class IconColumnPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('IconColumn')
                ->description('Read-only table column for blade-icons values stored by IconPickerField — optional label, color, and size.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    View::make('filament-flex-fields::partials.playground.icon-column-demo')
                        ->viewData([
                            'rows' => $this->demoRows(),
                        ]),
                ]),
        ];
    }

    /**
     * @return list<array{title: string, icon: string, labeled: string, detailed: string}>
     */
    protected function demoRows(): array
    {
        $iconColumn = IconColumn::make('menu_icon');

        $labeledColumn = IconColumn::make('status_icon')
            ->iconColor('success')
            ->iconSize('lg')
            ->showLabel();

        $detailedColumn = IconColumn::make('debug_icon')
            ->iconColor('warning')
            ->iconSize('sm')
            ->showLabel()
            ->showName();

        return [
            [
                'title' => 'Dashboard',
                'icon' => $iconColumn->formatIconDisplay('heroicon-o-home'),
                'labeled' => $labeledColumn->formatIconDisplay('heroicon-o-check-circle'),
                'detailed' => $detailedColumn->formatIconDisplay('heroicon-o-bolt'),
            ],
            [
                'title' => 'Settings',
                'icon' => $iconColumn->formatIconDisplay('heroicon-o-cog-6-tooth'),
                'labeled' => $labeledColumn->formatIconDisplay('heroicon-o-exclamation-triangle'),
                'detailed' => $detailedColumn->formatIconDisplay('gravityui-star'),
            ],
            [
                'title' => 'Draft entry',
                'icon' => $iconColumn->formatIconDisplay(null),
                'labeled' => $labeledColumn->formatIconDisplay(null),
                'detailed' => $detailedColumn->formatIconDisplay(null),
            ],
        ];
    }
}
