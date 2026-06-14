<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class FlexRadiolistPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'flex_radiolist__delivery' => 'express',
            'flex_radiolist__labels_only' => 'proposal',
            'flex_radiolist__sizes_sm' => 'documents',
            'flex_radiolist__sizes_md' => 'documents',
            'flex_radiolist__sizes_lg' => 'documents',
            'flex_radiolist__disabled_field' => 'documents',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            $this->section(),
        ];
    }

    public function section(): Section
    {
        return Section::make('Flex Radiolist')
            ->description('Single-select list rows with radio animation, optional icons, and locked disabled options.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                FlexRadiolist::make('flex_radiolist__delivery')
                    ->label('Delivery method')
                    ->helperText('Express selected · Standard available · Archived locked')
                    ->options([
                        'standard' => 'Standard',
                        'express' => 'Express',
                        'super_fast' => 'Super Fast',
                        'archived' => 'Archived',
                    ])
                    ->icons([
                        'standard' => GravityIcon::Trolley,
                        'express' => GravityIcon::Thunderbolt,
                        'super_fast' => GravityIcon::Rocket,
                        'archived' => GravityIcon::Archive,
                    ])
                    ->descriptions([
                        'standard' => '4–10 business days',
                        'express' => '2–5 business days',
                        'super_fast' => '1 business day',
                        'archived' => 'No longer available',
                    ])
                    ->disabledOptions(['archived']),
                FlexRadiolist::make('flex_radiolist__labels_only')
                    ->label('Files')
                    ->helperText('variant(label-only) — radio on the left, label only')
                    ->variant('label-only')
                    ->options([
                        'proposal' => 'Project proposal.pdf',
                        'report' => 'Q4 financial report.xlsx',
                        'guidelines' => 'Brand guidelines.fig',
                        'photo' => 'Team photo.jpg',
                        'notes' => 'Meeting notes.md',
                        'api' => 'API documentation.pdf',
                    ])
                    ->required(),
                Section::make('Sizes')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'lg' => 3])
                            ->extraAttributes(['class' => 'fff-playground-variants'])
                            ->schema([
                                FlexRadiolist::make('flex_radiolist__sizes_sm')
                                    ->label('Size · sm')
                                    ->size(ControlSize::Sm)
                                    ->options($this->fileOptions())
                                    ->icons($this->fileIcons())
                                    ->descriptions($this->fileDescriptions())
                                    ->disabledOptions(['archived']),
                                FlexRadiolist::make('flex_radiolist__sizes_md')
                                    ->label('Size · md')
                                    ->size(ControlSize::Md)
                                    ->options($this->fileOptions())
                                    ->icons($this->fileIcons())
                                    ->descriptions($this->fileDescriptions())
                                    ->disabledOptions(['archived']),
                                FlexRadiolist::make('flex_radiolist__sizes_lg')
                                    ->label('Size · lg')
                                    ->size(ControlSize::Lg)
                                    ->options($this->fileOptions())
                                    ->icons($this->fileIcons())
                                    ->descriptions($this->fileDescriptions())
                                    ->disabledOptions(['archived']),
                            ]),
                    ]),
                Section::make('Disabled field')
                    ->compact()
                    ->schema([
                        FlexRadiolist::make('flex_radiolist__disabled_field')
                            ->label('Disabled field')
                            ->helperText('->disabled()')
                            ->options($this->fileOptions())
                            ->icons($this->fileIcons())
                            ->descriptions($this->fileDescriptions())
                            ->disabled(),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected function fileOptions(): array
    {
        return [
            'documents' => 'Documents',
            'budget' => 'Budget.xlsx',
            'reports' => 'Reports.pdf',
            'archived' => 'Archived',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fileIcons(): array
    {
        return [
            'documents' => GravityIcon::Folder,
            'budget' => GravityIcon::LayoutCells,
            'reports' => GravityIcon::FileText,
            'archived' => GravityIcon::Archive,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function fileDescriptions(): array
    {
        return [
            'documents' => 'Updated 2 days ago',
            'budget' => 'Updated yesterday',
            'reports' => 'Updated 3 hours ago',
            'archived' => 'Read-only archive',
        ];
    }
}
