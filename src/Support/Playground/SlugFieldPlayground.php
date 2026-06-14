<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SlugFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'slug__one_liner_title' => 'Mediterranean Yacht Guide',
            'slug__one_liner_slug' => 'mediterranean-yacht-guide',
            'slug__title' => 'Luxury Yacht Charter in the Mediterranean',
            'slug__standalone' => 'luxury-yacht-charter-in-the-mediterranean',
            'slug__pair_title' => 'Premium Catamaran Experience',
            'slug__pair_slug' => 'premium-catamaran-experience',
            'slug__permalink' => 'mediterranean-sailing-guide',
            'slug__sandwich' => 'luxury-yacht',
            'slug__readonly' => 'readonly-slug',
            'slug__slug_readonly' => 'locked-permalink',
            'slug__homepage' => '/',
            'slug__i18n_title' => ['pl' => 'Przewodnik po Morzu Śródziemnym', 'en' => 'Mediterranean Sailing Guide'],
            'slug__i18n_slug' => 'przewodnik-po-morzu-srodziemnym',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Slug field')
                ->description('Permalink editor with inline Edit/OK/Cancel/Reset, auto-sync, unique validation hooks, Spatie Sluggable integration and FlexFields styling.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    FlexTextInput::make('slug__title')
                        ->label('Title (source)')
                        ->live()
                        ->columnSpanFull(),
                    SlugField::make('slug__standalone')
                        ->label('Slug')
                        ->source('slug__title')
                        ->helperText('Auto-syncs from title until you edit or reset the slug.')
                        ->columnSpanFull(),
                    TitleSlugField::make(
                        fieldTitle: 'slug__one_liner_title',
                        fieldSlug: 'slug__one_liner_slug',
                        urlHost: 'https://wyachts.test',
                        urlPath: '/posts/',
                    )
                        ->label('Title + slug (one-liner)')
                        ->columnSpanFull(),
                    TitleSlugField::make(
                        fieldTitle: 'slug__i18n_title',
                        fieldSlug: 'slug__i18n_slug',
                        translatableLocales: ['pl' => 'PL', 'en' => 'EN', 'fr' => 'FR'],
                        slugSourceLocale: 'pl',
                        urlHost: 'https://wyachts.test',
                        urlPath: '/guides/',
                    )
                        ->label('Translatable title + slug')
                        ->helperText('Single slug generated from the Polish title tab. Other locales do not change the permalink.')
                        ->columnSpanFull(),
                    SlugField::make('slug__pair_slug')
                        ->label('Title + slug pair')
                        ->titleField(
                            FlexTextInput::make('slug__pair_title')
                                ->label('Title')
                                ->placeholder('Enter a post title…'),
                        )
                        ->urlHost('https://wyachts.test')
                        ->urlPath('/blog/')
                        ->recordSlug('premium-catamaran-experience')
                        ->columnSpanFull(),
                    SlugField::make('slug__permalink')
                        ->label('Permalink preview')
                        ->source('slug__title')
                        ->urlHost('https://wyachts.test')
                        ->urlPath('/charters/')
                        ->visitRoute(fn (?string $slug): ?string => filled($slug) ? "https://wyachts.test/charters/{$slug}" : null)
                        ->generationDebounce(250)
                        ->columnSpanFull(),
                    SlugField::make('slug__sandwich')
                        ->label('URL slug sandwich')
                        ->source('slug__title')
                        ->urlHost('https://wyachts.test')
                        ->urlPath('/books/')
                        ->slugLabelPostfix('/detail/')
                        ->visitRoute(fn (?string $slug): ?string => filled($slug) ? "https://wyachts.test/books/{$slug}/detail" : null)
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SlugField::make('slug__readonly')
                                ->label('Form readonly')
                                ->urlHost('https://wyachts.test')
                                ->urlPath('/docs/')
                                ->readOnly(),
                            SlugField::make('slug__slug_readonly')
                                ->label('Slug readonly')
                                ->urlHost('https://wyachts.test')
                                ->urlPath('/docs/')
                                ->slugReadOnly(),
                            SlugField::make('slug__homepage')
                                ->label('Homepage slug')
                                ->allowHomepageSlug()
                                ->urlHost('https://wyachts.test')
                                ->slugPattern('/^(\/)?[a-z0-9]+(?:-[a-z0-9]+)*$/')
                                ->helperText('Supports "/" as homepage slug.'),
                        ]),
                ]),
        ];
    }
}
