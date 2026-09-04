<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;

class CompositionRecipesPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'composition__headline' => [
                'en' => 'Summer charter offer',
                'pl' => 'Oferta czarteru na lato',
            ],
            'composition__details_name' => 'Azure 52',
            'composition__details_port' => 'Split',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            PlaygroundRelatedHubs::view('composition-recipes'),
            Section::make('Composition recipes')
                ->description('Certified nesting: TranslatableFields locale tabs wrapping SegmentTabs with per-tab inputs — a common CMS / listing pattern.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    TranslatableFields::make('Headline')
                        ->locales(['en' => 'English', 'pl' => 'Polski'])
                        ->withRecommendedDefaults()
                        ->schema([
                            FlexTextInput::make('composition__headline')
                                ->label('Title')
                                ->required(),
                        ]),
                    SegmentTabs::make('Vessel details')
                        ->tabs([
                            SegmentTab::make('Basics')
                                ->icon(GravityIcon::Display)
                                ->schema([
                                    FlexTextInput::make('composition__details_name')
                                        ->label('Yacht name'),
                                    FlexTextInput::make('composition__details_port')
                                        ->label('Home port'),
                                ]),
                            SegmentTab::make('Ops')
                                ->icon(GravityIcon::Calendar)
                                ->schema([
                                    FlexTextInput::make('composition__details_crew')
                                        ->label('Crew size')
                                        ->numeric()
                                        ->default('6'),
                                ]),
                        ]),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

TranslatableFields::make('Headline')
    ->locales(['en' => 'English', 'pl' => 'Polski'])
    ->withRecommendedDefaults()
    ->schema([
        FlexTextInput::make('headline')
            ->label('Title')
            ->required(),
    ]);

SegmentTabs::make('Vessel details')
    ->tabs([
        SegmentTab::make('Basics')
            ->icon(GravityIcon::Display)
            ->schema([
                FlexTextInput::make('details_name')
                    ->label('Yacht name'),
                FlexTextInput::make('details_port')
                    ->label('Home port'),
            ]),
        SegmentTab::make('Ops')
            ->icon(GravityIcon::Calendar)
            ->schema([
                FlexTextInput::make('details_crew')
                    ->label('Crew size')
                    ->numeric()
                    ->default('6'),
            ]),
    ]);
PHP, filename: 'composition-recipes.php'),
                ]),
        ];
    }
}
