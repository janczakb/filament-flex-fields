<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class FlexChecklistPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'flex_checklist__documents' => ['documents'],
            'flex_checklist__sizes_sm' => ['documents'],
            'flex_checklist__sizes_md' => ['documents'],
            'flex_checklist__sizes_lg' => ['documents'],
            'flex_checklist__min_max' => ['documents', 'budget'],
            'flex_checklist__disabled_field' => ['documents'],
            'flex_checklist__no_icons' => ['documents'],
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
        return Section::make('Flex Checklist')
            ->description('Multi-select checklist rows with checkbox animation, icons, and locked disabled options.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                FlexChecklist::make('flex_checklist__documents')
                    ->label('Project files')
                    ->helperText('Documents selected · Budget unselected · Archived locked')
                    ->options([
                        'documents' => 'Documents',
                        'budget' => 'Budget.xlsx',
                        'reports' => 'Reports.pdf',
                        'archived' => 'Archived',
                        'old_backup' => 'Old backup.zip',
                    ])
                    ->icons([
                        'documents' => GravityIcon::Folder,
                        'budget' => GravityIcon::LayoutCells,
                        'reports' => GravityIcon::FileText,
                        'archived' => GravityIcon::Archive,
                        'old_backup' => GravityIcon::Server,
                    ])
                    ->descriptions([
                        'documents' => 'Shared project folder',
                        'budget' => 'Financial spreadsheet',
                        'reports' => 'Monthly performance report',
                        'archived' => 'Read-only archive',
                        'old_backup' => 'Legacy backup — cannot be selected',
                    ])
                    ->disabledOptions(['archived', 'old_backup']),
                FlexChecklist::make('flex_checklist__no_icons')
                    ->label('Without icons')
                    ->helperText('icons() is optional — labels and descriptions only')
                    ->options([
                        'documents' => 'Documents',
                        'budget' => 'Budget.xlsx',
                        'reports' => 'Reports.pdf',
                    ])
                    ->descriptions([
                        'documents' => 'Shared project folder',
                        'budget' => 'Financial spreadsheet',
                        'reports' => 'Monthly performance report',
                    ]),
                Section::make('Sizes')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'lg' => 3])
                            ->extraAttributes(['class' => 'fff-playground-variants'])
                            ->schema([
                                FlexChecklist::make('flex_checklist__sizes_sm')
                                    ->label('Size · sm')
                                    ->size(ControlSize::Sm)
                                    ->options($this->fileOptions())
                                    ->icons($this->fileIcons())
                                    ->descriptions($this->fileDescriptions())
                                    ->disabledOptions(['archived']),
                                FlexChecklist::make('flex_checklist__sizes_md')
                                    ->label('Size · md')
                                    ->size(ControlSize::Md)
                                    ->options($this->fileOptions())
                                    ->icons($this->fileIcons())
                                    ->descriptions($this->fileDescriptions())
                                    ->disabledOptions(['archived']),
                                FlexChecklist::make('flex_checklist__sizes_lg')
                                    ->label('Size · lg')
                                    ->size(ControlSize::Lg)
                                    ->options($this->fileOptions())
                                    ->icons($this->fileIcons())
                                    ->descriptions($this->fileDescriptions())
                                    ->disabledOptions(['archived']),
                            ]),
                    ]),
                Section::make('Validation · disabled')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'xl' => 2])
                            ->schema([
                                FlexChecklist::make('flex_checklist__min_max')
                                    ->label('Min / max selections')
                                    ->helperText('minSelections(1) · maxSelections(3) · required')
                                    ->options($this->fileOptions())
                                    ->icons($this->fileIcons())
                                    ->descriptions($this->fileDescriptions())
                                    ->disabledOptions(['archived'])
                                    ->minSelections(1)
                                    ->maxSelections(3)
                                    ->required(),
                                FlexChecklist::make('flex_checklist__disabled_field')
                                    ->label('Disabled field')
                                    ->helperText('->disabled()')
                                    ->options($this->fileOptions())
                                    ->icons($this->fileIcons())
                                    ->descriptions($this->fileDescriptions())
                                    ->disabled(),
                            ]),
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
