<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class TranslatableFieldsPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'translatable_pg__headline' => [
                'pl' => 'Przewodnik po Morzu Śródziemnym',
                'en' => 'Mediterranean Sailing Guide',
            ],
            'translatable_pg__pair_title' => [
                'pl' => 'Luksusowy czarter jachtu',
                'en' => 'Luxury yacht charter',
            ],
            'translatable_pg__pair_excerpt' => [
                'pl' => 'Krótki opis oferty po polsku.',
                'en' => 'Short offer summary in English.',
            ],
            'translatable_pg__block_title' => [
                'pl' => 'Oferta premium',
                'en' => 'Premium offer',
                'de' => 'Premium-Angebot',
            ],
            'translatable_pg__block_summary' => [
                'pl' => 'Zajawka widoczna na liście ofert.',
                'en' => 'Teaser shown on the listing page.',
                'de' => 'Teaser auf der Angebotsliste.',
            ],
            'translatable_pg__block_body' => [
                'pl' => 'Pełny opis oferty z detalami trasy, załogi i wyposażenia.',
                'en' => 'Full offer copy with route, crew, and equipment details.',
                'de' => 'Vollständige Angebotsbeschreibung mit Route, Crew und Ausstattung.',
            ],
            'translatable_pg__bordered_title' => [
                'pl' => 'Tytuł w obramowanych panelach',
                'en' => 'Title inside bordered panels',
            ],
            'translatable_pg__bordered_lead' => [
                'pl' => 'Lead po polsku.',
                'en' => 'Lead in English.',
            ],
            'translatable_pg__triple_tagline' => [
                'pl' => 'Żegluj z klasą',
                'en' => 'Sail with style',
                'de' => 'Segeln mit Stil',
            ],
            'translatable_pg__rtl_title' => [
                'ar' => 'مرحبا بالعالم',
            ],
            'translatable_pg__rtl_body' => [
                'ar' => 'محتوى عربي مع kierunkiem RTL.',
                'en' => 'English body with LTR direction.',
            ],
        ];
    }

    public function section(): Section
    {
        return Section::make('Translatable Fields')
            ->description('Locale tabs built on SegmentTabs — single inputs, multi-field groups per locale, bordered panels, RTL, and empty-tab badges.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Grid::make(['default' => 1, 'lg' => 2])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        TranslatableFields::make('Single field')
                            ->locales(['pl' => 'PL', 'en' => 'EN'])
                            ->withRecommendedDefaults()
                            ->schema([
                                FlexTextInput::make('translatable_pg__headline')
                                    ->label('Headline')
                                    ->placeholder('Page headline'),
                            ]),
                        TranslatableFields::make('Two-field group')
                            ->locales(['pl' => 'PL', 'en' => 'EN'])
                            ->withRecommendedDefaults()
                            ->schema([
                                FlexTextInput::make('translatable_pg__pair_title')
                                    ->label('Title')
                                    ->placeholder('Card title'),
                                FlexTextareaField::make('translatable_pg__pair_excerpt')
                                    ->label('Excerpt')
                                    ->placeholder('Short summary')
                                    ->rows(3),
                            ]),
                    ]),
                TranslatableFields::make('Three-field content block')
                    ->locales(['pl' => 'PL', 'en' => 'EN', 'de' => 'DE'])
                    ->withRecommendedDefaults()
                    ->schema([
                        FlexTextInput::make('translatable_pg__block_title')
                            ->label('Title'),
                        FlexTextInput::make('translatable_pg__block_summary')
                            ->label('Summary')
                            ->placeholder('Listing teaser'),
                        FlexTextareaField::make('translatable_pg__block_body')
                            ->label('Body')
                            ->placeholder('Long-form content')
                            ->rows(4),
                    ])
                    ->columnSpanFull(),
                Grid::make(['default' => 1, 'lg' => 2])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        TranslatableFields::make('Bordered panels')
                            ->locales(['pl' => 'PL', 'en' => 'EN'])
                            ->borderedPanels()
                            ->withRecommendedDefaults()
                            ->schema([
                                FlexTextInput::make('translatable_pg__bordered_title')
                                    ->label('Title'),
                                FlexTextareaField::make('translatable_pg__bordered_lead')
                                    ->label('Lead')
                                    ->rows(2),
                            ]),
                        TranslatableFields::make('One input, three locales')
                            ->locales(['pl' => 'PL', 'en' => 'EN', 'de' => 'DE'])
                            ->withRecommendedDefaults()
                            ->schema([
                                FlexTextInput::make('translatable_pg__triple_tagline')
                                    ->label('Tagline')
                                    ->placeholder('Marketing tagline'),
                            ]),
                    ]),
                TranslatableFields::make('RTL + empty badges')
                    ->locales(['ar' => 'Arabic', 'en' => 'English'])
                    ->directionByLocale()
                    ->emptyBadgeWhenAllFieldsAreEmpty()
                    ->activeTabWithValue()
                    ->schema([
                        FlexTextInput::make('translatable_pg__rtl_title')
                            ->label('Title'),
                        FlexTextareaField::make('translatable_pg__rtl_body')
                            ->label('Body')
                            ->rows(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [$this->section()];
    }
}
