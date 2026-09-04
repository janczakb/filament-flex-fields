<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;

class SelectPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'select__filament' => 'published',
            'select__filament_html' => 'published',
            'select__long_labels' => 'enterprise_agreement',
            'select__basic' => 'published',
            'select__searchable' => 'tailwind',
            'select__multiple' => ['action', 'adventure', 'drama', 'comedy'],
            'select__multiple_checklist' => ['california', 'texas', 'delaware'],
            'select__email_recipients' => ['jane', 'john'],
            'select__custom_value_user' => 'fred',
            'select__grouped' => 'usa',
            'select__disabled_animals' => 'dog',
            'select__async_paginated' => null,
            'select__rich' => 'pro',
            'select__rich_icon_title' => 'pro',
            'select__rich_title_desc' => 'pro',
            'select__grid' => 'sky',
            'select__disabled' => 'published',
            'select__required' => null,
            'select__disabled_options' => 'draft',
            'select__dynamic_options' => 'published',
            'select__truncate_labels' => 'enterprise_agreement',
            'select__reorderable' => ['tailwind', 'laravel', 'livewire', 'alpine'],
            'select__boolean' => null,
            'select__sm' => 'draft',
            'select__md' => 'reviewing',
            'select__lg' => 'published',
            'select__multiple_sm' => ['action', 'adventure', 'drama', 'comedy'],
            'select__multiple_md' => ['action', 'adventure', 'drama', 'comedy'],
            'select__multiple_lg' => ['action', 'adventure', 'drama', 'comedy'],
            'select__rich_sm' => 'pro',
            'select__rich_md' => 'pro',
            'select__rich_lg' => 'pro',
            'select__flat' => 'published',
            'select__faded' => 'published',
            'select__underlined' => 'published',
            'select__bordered' => 'published',
            'select__secondary' => 'published',
            'select__soft' => 'published',
            'select__item_card' => 'published',
            'select__inline_search' => 'tailwind',
            'select__entity_mentions' => ['jane', 'john'],
            'select__inline_field_label' => 'published',
            'select__clearable' => 'published',
            'select__not_clearable' => 'published',
            'select__selectable_placeholder_false' => 'published',
            'select__domain_affix' => 'acme',
            'select__prefix_icon' => 'tailwind',
            'select__color' => 'published',
            'select__chip_primary' => ['action', 'comedy'],
            'select__chip_success' => ['adventure', 'drama'],
            'select__chip_danger' => ['horror', 'thriller'],
            'select__rounding_full' => 'published',
            'select__focus_outline' => 'published',
            'select__dropdown_align_start' => 'published',
            'select__dropdown_align_end' => 'published',
            'select__custom_trigger_icons' => 'published',
            'select__custom_check_icon' => ['california', 'texas'],
            'select__scale_10k' => null,
            'select__cascade_country' => 'us',
            'select__cascade_region' => null,
            'select__rtl' => 'riyadh',
            'select__rtl_inline' => 'laravel',
            'select__rtl_inline_field_label' => 'riyadh',
            'select__rtl_hebrew_inline' => 'tel_aviv',
            'select__rtl_dropdown_clearable' => 'jeddah',
            'select__create_single' => null,
            'select__create_multiple' => ['laravel'],
            'select__create_with_sections' => 'tailwind',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function compactGenreOptions(): array
    {
        return [
            'action' => 'Action',
            'adventure' => 'Adventure',
            'comedy' => 'Comedy',
            'drama' => 'Drama',
            'horror' => 'Horror',
            'thriller' => 'Thriller',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function genreOptions(): array
    {
        return [
            'action' => 'Action',
            'adventure' => 'Adventure',
            'animation' => 'Animation',
            'biography' => 'Biography',
            'comedy' => 'Comedy',
            'crime' => 'Crime',
            'documentary' => 'Documentary',
            'drama' => 'Drama',
            'family' => 'Family',
            'fantasy' => 'Fantasy',
            'film_noir' => 'Film-Noir',
            'history' => 'History',
            'horror' => 'Horror',
            'music' => 'Music',
            'musical' => 'Musical',
            'mystery' => 'Mystery',
            'romance' => 'Romance',
            'sci_fi' => 'Sci-Fi',
            'sport' => 'Sport',
            'thriller' => 'Thriller',
            'war' => 'War',
            'western' => 'Western',
            'superhero' => 'Superhero',
            'indie' => 'Indie',
            'short' => 'Short',
            'news' => 'News',
            'reality' => 'Reality-TV',
            'game_show' => 'Game-Show',
            'talk_show' => 'Talk-Show',
            'adult' => 'Adult',
            'experimental' => 'Experimental',
            'anthology' => 'Anthology',
            'satire' => 'Satire',
            'disaster' => 'Disaster',
            'spy' => 'Spy',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $statusOptions = [
            'draft' => 'Draft',
            'reviewing' => 'Reviewing',
            'published' => 'Published',
        ];

        $longLabelOptions = [
            'short' => 'Krótka opcja',
            'medium' => 'Średnio długa nazwa opcji w liście rozwijanej',
            'long' => 'Bardzo długa etykieta opcji, która powinna wymusić szerszy dropdown niż sam trigger pola select',
            'enterprise_agreement' => 'Enterprise Master Service Agreement with dedicated onboarding, premium support and custom SLA terms',
        ];

        $techOptions = [
            'tailwind' => 'Tailwind CSS',
            'alpine' => 'Alpine.js',
            'laravel' => 'Laravel',
            'livewire' => 'Livewire',
        ];

        $usStateOptions = [
            'florida' => 'Florida',
            'delaware' => 'Delaware',
            'california' => 'California',
            'texas' => 'Texas',
            'new_york' => 'New York',
            'washington' => 'Washington',
            'oregon' => 'Oregon',
            'nevada' => 'Nevada',
            'arizona' => 'Arizona',
            'colorado' => 'Colorado',
        ];

        $countrySectionOptions = [
            'North America' => [
                'usa' => 'United States',
                'canada' => 'Canada',
                'mexico' => 'Mexico',
            ],
            'Europe' => [
                'uk' => 'United Kingdom',
                'france' => 'France',
                'germany' => 'Germany',
                'spain' => 'Spain',
                'italy' => 'Italy',
            ],
            'Asia' => [
                'japan' => 'Japan',
                'china' => 'China',
                'india' => 'India',
                'south_korea' => 'South Korea',
            ],
        ];

        $animalOptions = [
            'dog' => 'Dog',
            'cat' => 'Cat',
            'bird' => 'Bird',
            'kangaroo' => 'Kangaroo',
            'elephant' => 'Elephant',
            'tiger' => 'Tiger',
        ];

        $customValueUserOptions = [
            'bob' => [
                'label' => 'Bob',
                'description' => 'bob@example.com',
            ],
            'fred' => [
                'label' => 'Fred',
                'description' => 'fred@example.com',
            ],
            'martha' => [
                'label' => 'Martha',
                'description' => 'martha@example.com',
            ],
        ];

        $richOptions = [
            'basic' => [
                'label' => 'Basic',
                'description' => 'Essential features only',
                'icon' => GravityIcon::Star,
                'badge' => 'Free',
                'badge_color' => 'success',
            ],
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced analytics and support',
                'icon' => GravityIcon::Thunderbolt,
                'badge' => 'Popular',
                'badge_color' => 'primary',
            ],
            'enterprise' => [
                'label' => 'Enterprise',
                'description' => 'Custom SLA and onboarding',
                'icon' => GravityIcon::OfficeBadge,
                'badge' => 'New',
                'badge_color' => 'warning',
            ],
        ];

        $richIconTitleOptions = [
            'basic' => [
                'label' => 'Basic',
                'icon' => GravityIcon::Star,
            ],
            'pro' => [
                'label' => 'Pro',
                'icon' => GravityIcon::Thunderbolt,
            ],
            'enterprise' => [
                'label' => 'Enterprise',
                'icon' => GravityIcon::OfficeBadge,
            ],
        ];

        $richTitleDescOptions = [
            'basic' => [
                'label' => 'Basic',
                'desc' => 'Essential features only',
            ],
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced analytics and support',
            ],
            'enterprise' => [
                'label' => 'Enterprise',
                'description' => 'Custom SLA and onboarding',
            ],
        ];

        $themeOptions = [
            'default' => [
                'label' => 'Default',
                'icon' => GravityIcon::Palette,
                'badge_color' => 'primary',
            ],
            'sky' => [
                'label' => 'Sky',
                'icon' => GravityIcon::Cloud,
                'badge_color' => 'primary',
            ],
            'mint' => [
                'label' => 'Mint',
                'icon' => GravityIcon::MagicWand,
                'badge_color' => 'success',
            ],
            'rose' => [
                'label' => 'Rose',
                'icon' => GravityIcon::Heart,
                'badge_color' => 'danger',
            ],
            'amber' => [
                'label' => 'Amber',
                'icon' => GravityIcon::Sun,
                'badge_color' => 'warning',
            ],
            'slate' => [
                'label' => 'Slate',
                'icon' => GravityIcon::Moon,
                'badge_color' => 'neutral',
            ],
            'violet' => [
                'label' => 'Violet',
                'icon' => GravityIcon::Flask,
                'badge_color' => 'primary',
            ],
            'ocean' => [
                'label' => 'Ocean',
                'image' => 'https://ui-avatars.com/api/?name=Ocean&background=10b981&color=fff&size=64',
                'badge_color' => 'success',
            ],
        ];

        $domainOptions = [
            'acme' => 'acme',
            'example' => 'example',
            'myshop' => 'myshop',
        ];

        return [
            Section::make('SelectField')
                ->description('Core SelectField demos: search, multi-select, rich options, grid layout, dynamic loading, and Filament parity.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Select::make('select__filament')
                        ->label('Filament Select (original)')
                        ->helperText('Standardowy Filament Select — JS dropdown (native(false)), bez stylów SelectField.')
                        ->options($statusOptions)
                        ->native(false),
                    Select::make('select__filament_html')
                        ->label('Filament HTML native')
                        ->helperText('Prawdziwy element <select> w przeglądarce (native). Porównaj z JS dropdown powyżej i SelectField poniżej.')
                        ->options($statusOptions)
                        ->native(),
                    SelectField::make('select__long_labels')
                        ->label('Long option labels')
                        ->helperText('Test szerokości dropdownu przy długich etykietach opcji.')
                        ->options($longLabelOptions)
                        ->searchable(),
                    SelectField::make('select__basic')
                        ->label('Status')
                        ->options($statusOptions)
                        ->placeholder('Choose status'),
                    SelectField::make('select__searchable')
                        ->label('Searchable technologies')
                        ->options($techOptions)
                        ->searchable(),
                    SelectField::make('select__multiple')
                        ->label('Multiple genres (wrap demo)')
                        ->helperText('Chip/tag mode: selected options leave the dropdown list. Use checklist mode below to keep them visible.')
                        ->options($this->genreOptions())
                        ->multiple()
                        ->searchable()
                        ->chipColor('neutral')
                        ->columnSpanFull(),
                    SelectField::make('select__multiple_checklist')
                        ->label('Multiple states (checklist)')
                        ->helperText('Classic multi-select: options stay in the list with checkmarks; the dropdown stays open while you toggle.')
                        ->options($usStateOptions)
                        ->multiple()
                        ->searchable()
                        ->keepSelectedOptionsInDropdown()
                        ->chipColor('neutral')
                        ->columnSpanFull(),
                    SelectField::make('select__email_recipients')
                        ->label('Email recipients')
                        ->helperText('Two-line options (name + email) with chipLabel — chips show email only while the dropdown keeps the full row.')
                        ->options([
                            'jane' => [
                                'label' => 'Jane Cooper',
                                'description' => 'jane.cooper@example.com',
                                'chipLabel' => 'jane.cooper@example.com',
                            ],
                            'john' => [
                                'label' => 'John Smith',
                                'description' => 'john.smith@example.com',
                                'chipLabel' => 'john.smith@example.com',
                            ],
                            'alex' => [
                                'label' => 'Alex Rivera',
                                'description' => 'alex.rivera@example.com',
                                'chipLabel' => 'alex.rivera@example.com',
                            ],
                        ])
                        ->multiple()
                        ->searchable()
                        ->keepSelectedOptionsInDropdown()
                        ->richOptions()
                        ->chipColor('neutral')
                        ->columnSpanFull(),
                    SelectField::make('select__custom_value_user')
                        ->label('Custom value (optionView)')
                        ->helperText('optionView() — custom Blade per option: avatar + name in trigger, avatar + name + email in list (like render props).')
                        ->options($customValueUserOptions)
                        ->searchable()
                        ->default('fred')
                        ->optionView('filament-flex-fields::forms.components.partials.select-option-view-user'),
                    SelectField::make('select__grouped')
                        ->label('Country (sections)')
                        ->helperText('Grouped continents with optionGroupSeparators() — matches Autocomplete With Sections.')
                        ->options($countrySectionOptions)
                        ->searchable()
                        ->optionGroupSeparators(),
                    SelectField::make('select__disabled_animals')
                        ->label('Disabled animals')
                        ->helperText('disabledOptions([\'cat\', \'kangaroo\']) — visible but not selectable, like Autocomplete disabled keys.')
                        ->options($animalOptions)
                        ->default('dog')
                        ->searchable()
                        ->disabledOptions(['cat', 'kangaroo']),
                    SelectField::make('select__async_paginated')
                        ->label('Async paginated characters')
                        ->helperText('paginatedSearchResults() + getSearchResultsPageUsing() — scroll the list to load more rows.')
                        ->searchable()
                        ->searchResultsPageSize(12)
                        ->paginatedSearchResults()
                        ->getSearchResultsPageUsing(fn (string $search, ?string $cursor, int $pageSize): array => $this->paginatedCharacterSearch($search, $cursor, $pageSize)),
                    SelectField::make('select__disabled_options')
                        ->label('Disabled options')
                        ->helperText('Published is disabled via disableOptionWhen() — visible but not selectable.')
                        ->options($statusOptions)
                        ->default('draft')
                        ->disableOptionWhen(fn (string $value): bool => $value === 'published'),
                    SelectField::make('select__dynamic_options')
                        ->label('Dynamic options loading')
                        ->helperText('options(fn…) — options load when the dropdown opens; centered spinner + translated loading label.')
                        ->options(function () use ($statusOptions): array {
                            usleep(400_000);

                            return $statusOptions;
                        })
                        ->default('published')
                        ->searchable(),
                    SelectField::make('select__truncate_labels')
                        ->label('Truncated labels')
                        ->helperText('wrapOptionLabels(false) truncates long labels in the trigger and dropdown.')
                        ->options($longLabelOptions)
                        ->searchable()
                        ->wrapOptionLabels(false),
                    SelectField::make('select__reorderable')
                        ->label('Reorderable multi-select')
                        ->helperText('Multi-select only: drag chips in the closed trigger (cursor: move) to change value order — not dropdown options.')
                        ->options($techOptions)
                        ->multiple()
                        ->searchable()
                        ->reorderable(),
                    SelectField::make('select__boolean')
                        ->label('Boolean select')
                        ->helperText('Filament boolean() helper — yes/no placeholder options.')
                        ->boolean(placeholder: 'Make your mind up...'),
                    SelectField::make('select__rich')
                        ->label('Rich options (icon + title + desc)')
                        ->helperText('Closed trigger stays medium height (icon + title). Description and badges show in the dropdown.')
                        ->options($richOptions)
                        ->searchable()
                        ->richOptions(),
                    Grid::make(['default' => 1, 'sm' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__rich_icon_title')
                                ->label('Rich · icon + title')
                                ->options($richIconTitleOptions)
                                ->searchable()
                                ->richOptions(),
                            SelectField::make('select__rich_title_desc')
                                ->label('Rich · title + desc')
                                ->options($richTitleDescOptions)
                                ->searchable()
                                ->richOptions(),
                        ]),
                    SelectField::make('select__grid')
                        ->label('Theme picker (grid layout)')
                        ->options($themeOptions)
                        ->optionLayout('grid')
                        ->searchable()
                        ->richOptions(),
                    SelectField::make('select__disabled')
                        ->label('Disabled')
                        ->options($statusOptions)
                        ->disabled(),
                    SelectField::make('select__required')
                        ->label('Required status')
                        ->options($statusOptions)
                        ->required(),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

SelectField::make('status')
    ->label('Status')
    ->options([
        'draft' => 'Draft',
        'published' => 'Published',
    ])
    ->searchable()
    ->placeholder('Choose status');
PHP),
                ]),
            Section::make('SelectField — Sizes & surfaces')
                ->description('Track sizes, multi-select chip sizes, rich option density, surface variants, and the item-card trigger.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__sm')
                                ->label('Small')
                                ->options($statusOptions)
                                ->size('sm'),
                            SelectField::make('select__md')
                                ->label('Medium')
                                ->options($statusOptions),
                            SelectField::make('select__lg')
                                ->label('Large')
                                ->options($statusOptions)
                                ->size('lg'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__multiple_sm')
                                ->label('Multiple chips · Small')
                                ->options($this->compactGenreOptions())
                                ->multiple()
                                ->searchable()
                                ->chipColor('neutral')
                                ->size('sm'),
                            SelectField::make('select__multiple_md')
                                ->label('Multiple chips · Medium')
                                ->options($this->compactGenreOptions())
                                ->multiple()
                                ->searchable()
                                ->chipColor('neutral'),
                            SelectField::make('select__multiple_lg')
                                ->label('Multiple chips · Large')
                                ->options($this->compactGenreOptions())
                                ->multiple()
                                ->searchable()
                                ->chipColor('neutral')
                                ->size('lg'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__rich_sm')
                                ->label('Rich · Small')
                                ->options($richOptions)
                                ->searchable()
                                ->richOptions()
                                ->size('sm'),
                            SelectField::make('select__rich_md')
                                ->label('Rich · Medium')
                                ->options($richOptions)
                                ->searchable()
                                ->richOptions(),
                            SelectField::make('select__rich_lg')
                                ->label('Rich · Large')
                                ->options($richOptions)
                                ->searchable()
                                ->richOptions()
                                ->size('lg'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__bordered')
                                ->label('Bordered (default)')
                                ->options($statusOptions)
                                ->variant('bordered'),
                            SelectField::make('select__flat')
                                ->label('Flat')
                                ->options($statusOptions)
                                ->variant('flat'),
                            SelectField::make('select__soft')
                                ->label('Soft')
                                ->options($statusOptions)
                                ->variant('soft'),
                            SelectField::make('select__faded')
                                ->label('Faded')
                                ->options($statusOptions)
                                ->variant('faded'),
                            SelectField::make('select__underlined')
                                ->label('Underlined')
                                ->options($statusOptions)
                                ->variant('underlined'),
                            SelectField::make('select__secondary')
                                ->label('Secondary')
                                ->options($statusOptions)
                                ->variant('secondary'),
                        ]),
                    SelectField::make('select__item_card')
                        ->label('Item card variant')
                        ->helperText('variant(\'item-card\') — compact card-style trigger for item-card groups and SaaS settings rows.')
                        ->options($statusOptions)
                        ->variant('item-card')
                        ->columnSpanFull(),
                    PlaygroundCodeSnippet::make(<<<'PHP'
SelectField::make('theme')
    ->size('sm')
    ->variant('secondary');

SelectField::make('row')
    ->variant('item-card');
PHP),
                ]),
            Section::make('SelectField — Configuration API')
                ->description('Every SelectField setup method from the docs: search UX, clear/placeholder, affixes, colors, rounding, focus outline, dropdown alignment, and custom icons.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__inline_search')
                                ->label('Inline search')
                                ->helperText('inlineSearch() — search input lives inside the trigger instead of the dropdown header.')
                                ->options($techOptions)
                                ->searchable()
                                ->inlineSearch()
                                ->clearable(),
                            SelectField::make('select__entity_mentions')
                                ->label('Entity mentions')
                                ->helperText('entityMentions() — type @ in the closed trigger or search box to pick people asynchronously.')
                                ->multiple()
                                ->searchable()
                                ->entityMentions(trigger: '@')
                                ->getSearchResultsUsing(function (string $search): array {
                                    $people = [
                                        'jane' => 'Jane Cooper',
                                        'john' => 'John Smith',
                                        'alex' => 'Alex Rivera',
                                        'sam' => 'Sam Patel',
                                    ];

                                    $needle = strtolower(trim($search));

                                    if ($needle === '') {
                                        return $people;
                                    }

                                    return array_filter(
                                        $people,
                                        fn (string $label, string $key): bool => str_contains(strtolower($label), $needle)
                                            || str_contains($key, $needle),
                                        ARRAY_FILTER_USE_BOTH,
                                    );
                                }),
                            SelectField::make('select__inline_field_label')
                                ->label('Status')
                                ->helperText('inlineFieldLabel() — field label renders inside the trigger track.')
                                ->options($statusOptions)
                                ->inlineFieldLabel(),
                        ]),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__clearable')
                                ->label('Clearable (default)')
                                ->helperText('clearable() — × button when a value is selected (default for bordered variant).')
                                ->options($statusOptions),
                            SelectField::make('select__not_clearable')
                                ->label('Not clearable')
                                ->helperText('clearable(false) — hides the clear button.')
                                ->options($statusOptions)
                                ->clearable(false),
                        ]),
                    SelectField::make('select__selectable_placeholder_false')
                        ->label('Placeholder not re-selectable')
                        ->helperText('selectablePlaceholder(false) — after choosing a value you cannot pick the empty placeholder again.')
                        ->options($statusOptions)
                        ->selectablePlaceholder(false)
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__domain_affix')
                                ->label('Domain (inline affixes)')
                                ->helperText('prefix()/suffix() with isInline: true — visual only; stored state is the option key.')
                                ->prefix('https://', isInline: true)
                                ->suffix('.com', isInline: true)
                                ->options($domainOptions)
                                ->searchable(),
                            SelectField::make('select__prefix_icon')
                                ->label('Prefix icon (inline)')
                                ->helperText('prefixIcon(..., isInline: true) — leading icon inside the trigger shell.')
                                ->prefixIcon(GravityIcon::Globe, isInline: true)
                                ->options($techOptions)
                                ->searchable(),
                        ]),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__dropdown_align_start')
                                ->label('Dropdown align · start')
                                ->helperText('dropdownAlign(\'start\') — panel anchors to the leading edge of the trigger.')
                                ->options($statusOptions)
                                ->searchable()
                                ->dropdownAlign('start'),
                            SelectField::make('select__dropdown_align_end')
                                ->label('Dropdown align · end')
                                ->helperText('dropdownAlign(\'end\') — panel anchors to the trailing edge of the trigger.')
                                ->options($statusOptions)
                                ->searchable()
                                ->dropdownAlign('end'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__color')
                                ->label('Accent color')
                                ->helperText('color(\'primary\') — fi-color-* wrapper class.')
                                ->options($statusOptions)
                                ->color('primary'),
                            SelectField::make('select__rounding_full')
                                ->label('Full rounding')
                                ->helperText('rounding(\'full\') — pill-shaped trigger.')
                                ->options($statusOptions)
                                ->rounding('full'),
                            SelectField::make('select__focus_outline')
                                ->label('Focus outline')
                                ->helperText('focusOutline() — visible focus ring on keyboard focus.')
                                ->options($statusOptions)
                                ->focusOutline(),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__chip_primary')
                                ->label('Chips · primary')
                                ->helperText('chipColor(\'primary\')')
                                ->options($this->compactGenreOptions())
                                ->multiple()
                                ->searchable()
                                ->chipColor('primary'),
                            SelectField::make('select__chip_success')
                                ->label('Chips · success')
                                ->helperText('chipColor(\'success\')')
                                ->options($this->compactGenreOptions())
                                ->multiple()
                                ->searchable()
                                ->chipColor('success'),
                            SelectField::make('select__chip_danger')
                                ->label('Chips · danger')
                                ->helperText('chipColor(\'danger\')')
                                ->options($this->compactGenreOptions())
                                ->multiple()
                                ->searchable()
                                ->chipColor('danger'),
                        ]),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__custom_trigger_icons')
                                ->label('Custom chevron & clear icons')
                                ->helperText('chevronIcon() + clearIcon() — override default Gravity UI trigger icons.')
                                ->options($statusOptions)
                                ->chevronIcon(GravityIcon::CircleChevronDown)
                                ->clearIcon(GravityIcon::CircleXmark),
                            SelectField::make('select__custom_check_icon')
                                ->label('Custom checklist check icon')
                                ->helperText('selectedOptionCheckIcon() — used in keepSelectedOptionsInDropdown() rows.')
                                ->options($usStateOptions)
                                ->multiple()
                                ->searchable()
                                ->keepSelectedOptionsInDropdown()
                                ->selectedOptionCheckIcon(GravityIcon::SealCheck)
                                ->chipColor('neutral'),
                        ]),
                    PlaygroundCodeSnippet::make(<<<'PHP'
SelectField::make('tech')
    ->options($options)
    ->searchable()
    ->inlineSearch()
    ->clearable()
    ->prefixIcon(GravityIcon::Magnifier)
    ->dropdownAlign('end');
PHP),
                ]),
            Section::make('Smart suggest · create option')
                ->description('Headless combobox allowCreateOption() — type a label that is not in the list, then pick the create row to commit it as the value (inline, no modal).')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__create_single')
                                ->label('Create option · single')
                                ->helperText('Search for a framework that is not listed (e.g. "Svelte") — a Create row appears at the top of the dropdown.')
                                ->options($techOptions)
                                ->searchable()
                                ->allowCreateOption()
                                ->placeholder('Search or create…'),
                            SelectField::make('select__create_multiple')
                                ->label('Create option · multiple')
                                ->helperText('Same create row in multi-select — each created value becomes a chip. minItems(1) validates on submit; maxItems(3) blocks extra picks with an in-menu message.')
                                ->options($techOptions)
                                ->multiple()
                                ->searchable()
                                ->allowCreateOption()
                                ->minItems(1)
                                ->maxItems(3)
                                ->chipColor('primary'),
                        ]),
                    SelectField::make('select__modal_create')
                        ->label('Create option · modal form (Filament)')
                        ->helperText('createOptionForm() + createOptionUsing() — multi-field modal (not the inline Create row). After save, the new value is selected and the label refreshes.')
                        ->options([
                            'alpha' => 'Alpha',
                            'beta' => 'Beta',
                        ])
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Name')
                                ->required(),
                            TextInput::make('code')
                                ->label('Code')
                                ->required(),
                        ])
                        ->createOptionUsing(function (array $data): string {
                            return 'created-'.str($data['code'] ?? $data['name'] ?? 'option')->slug().'-'.substr(uniqid(), -4);
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $value = (string) $value;

                            return match ($value) {
                                'alpha' => 'Alpha',
                                'beta' => 'Beta',
                                default => str_starts_with($value, 'created-')
                                    ? 'Created · '.str($value)->after('created-')->beforeLast('-')->replace('-', ' ')->title()
                                    : $value,
                            };
                        })
                        ->columnSpanFull(),
                    SelectField::make('select__create_with_sections')
                        ->label('Create + recent & suggested')
                        ->helperText('recentOptions() and suggestedOptions() sections appear when the query is empty; create row appears once you type a new label.')
                        ->options($techOptions)
                        ->searchable()
                        ->allowCreateOption()
                        ->recentOptions(['livewire', 'alpine'])
                        ->suggestedOptions(['tailwind', 'laravel'])
                        ->columnSpanFull(),
                    PlaygroundCodeSnippet::make(<<<'PHP'
SelectField::make('tag')
    ->options($existingTags)
    ->searchable()
    ->allowCreateOption()
    ->recentOptions(['laravel', 'filament'])
    ->suggestedOptions(['tailwindcss']);
PHP),
                ]),
            Section::make('Scale · cascading · RTL')
                ->description('Virtualized 10k options, dependsOn() cascading selects, and dir=rtl previews (dropdown search vs inlineSearch).')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    SelectField::make('select__scale_10k')
                        ->label('10k options (virtualized)')
                        ->helperText('Headless combobox keeps a ~50-row window once options ≥ 100 — scroll or arrow through 10 000 rows without mounting the full DOM.')
                        ->options(fn (): array => $this->tenThousandOptions())
                        ->searchable()
                        ->placeholder('Search option…'),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__cascade_country')
                                ->label('Country (parent)')
                                ->helperText('->live() + skipRenderAfterStateUpdated() so Region stays clickable (no full-page morph). Options still re-resolve via getOptionsForJs when Region opens.')
                                ->options([
                                    'us' => 'United States',
                                    'pl' => 'Poland',
                                    'ae' => 'United Arab Emirates',
                                ])
                                ->live()
                                ->skipRenderAfterStateUpdated()
                                ->afterStateUpdated(fn (Set $set) => $set('select__cascade_region', null))
                                ->searchable(),
                            SelectField::make('select__cascade_region')
                                ->label('Region (dependsOn)')
                                ->helperText('Options refetch when the dropdown opens after the country changes. Empty until a country is chosen is expected.')
                                ->dependsOn('select__cascade_country', fn (?string $country): array => match ($country) {
                                    'us' => [
                                        'ca' => 'California',
                                        'tx' => 'Texas',
                                        'ny' => 'New York',
                                    ],
                                    'pl' => [
                                        'mz' => 'Mazowieckie',
                                        'wp' => 'Wielkopolskie',
                                        'pm' => 'Pomorskie',
                                    ],
                                    'ae' => [
                                        'du' => 'Dubai',
                                        'az' => 'Abu Dhabi',
                                        'sh' => 'Sharjah',
                                    ],
                                    default => [],
                                })
                                ->searchable()
                                ->placeholder('Pick a country first'),
                        ]),
                    SelectField::make('select__rtl')
                        ->label('RTL · dropdown search')
                        ->helperText('dir=rtl + searchable() — search field in the dropdown header (filled × clears query).')
                        ->options($this->arabicCityOptions())
                        ->searchable()
                        ->clearable()
                        ->extraAttributes(['dir' => 'rtl']),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__rtl_inline')
                                ->label('RTL · inline search')
                                ->helperText('dir=rtl + inlineSearch() + clearable() — trigger input + default clear (×) + chevron; no dropdown search ×.')
                                ->options($this->arabicTechOptions())
                                ->searchable()
                                ->inlineSearch()
                                ->clearable()
                                ->extraAttributes(['dir' => 'rtl']),
                            SelectField::make('select__rtl_inline_field_label')
                                ->label('RTL · inline search + field label')
                                ->helperText('inlineFieldLabel() inside RTL trigger — affix order with Arabic labels.')
                                ->options($this->arabicCityOptions())
                                ->searchable()
                                ->inlineSearch()
                                ->inlineFieldLabel()
                                ->clearable()
                                ->extraAttributes(['dir' => 'rtl']),
                            SelectField::make('select__rtl_hebrew_inline')
                                ->label('RTL · Hebrew inline search')
                                ->helperText('dir=rtl on he-IL labels — second RTL locale smoke test.')
                                ->options($this->hebrewCityOptions())
                                ->searchable()
                                ->inlineSearch()
                                ->clearable()
                                ->extraAttributes(['dir' => 'rtl', 'lang' => 'he']),
                            SelectField::make('select__rtl_dropdown_clearable')
                                ->label('RTL · dropdown + prefix icon')
                                ->helperText('Dropdown search with prefixIcon + dropdownAlign(\'end\') in RTL.')
                                ->options($this->arabicCityOptions())
                                ->searchable()
                                ->clearable()
                                ->prefixIcon(GravityIcon::Magnifier)
                                ->dropdownAlign('end')
                                ->extraAttributes(['dir' => 'rtl']),
                        ]),
                    PlaygroundCodeSnippet::make(<<<'PHP'
SelectField::make('region')
    ->dependsOn('country', fn (?string $country): array => match ($country) {
        'pl' => ['mz' => 'Mazowieckie', 'wp' => 'Wielkopolskie'],
        default => [],
    })
    ->searchable();

SelectField::make('city')
    ->options($arabicCities)
    ->searchable()
    ->inlineSearch()
    ->clearable()
    ->extraAttributes(['dir' => 'rtl']);
PHP),
                ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function arabicCityOptions(): array
    {
        return [
            'riyadh' => 'الرياض',
            'jeddah' => 'جدة',
            'dammam' => 'الدمام',
            'dubai' => 'دبي',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function arabicTechOptions(): array
    {
        return [
            'tailwind' => 'تايلويند CSS',
            'alpine' => 'Alpine.js',
            'laravel' => 'لارavel',
            'livewire' => 'Livewire',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function hebrewCityOptions(): array
    {
        return [
            'tel_aviv' => 'תל אביב',
            'jerusalem' => 'ירושלים',
            'haifa' => 'חיפה',
            'beer_sheva' => 'באר שבע',
        ];
    }

    /**
     * @return array{items: list<array{value: string, label: string}>, cursor: ?string, hasMore: bool}
     */
    private function paginatedCharacterSearch(string $search, ?string $cursor, int $pageSize): array
    {
        $catalog = [
            'luke' => 'Luke Skywalker',
            'leia' => 'Leia Organa',
            'han' => 'Han Solo',
            'chewbacca' => 'Chewbacca',
            'obiwan' => 'Obi-Wan Kenobi',
            'yoda' => 'Yoda',
            'vader' => 'Darth Vader',
            'palpatine' => 'Emperor Palpatine',
            'maul' => 'Darth Maul',
            'padme' => 'Padmé Amidala',
            'anakin' => 'Anakin Skywalker',
            'ahsoka' => 'Ahsoka Tano',
            'mando' => 'Din Djarin',
            'grogu' => 'Grogu',
            'finn' => 'Finn',
            'rey' => 'Rey',
            'poe' => 'Poe Dameron',
            'bb8' => 'BB-8',
            'lando' => 'Lando Calrissian',
            'boba' => 'Boba Fett',
            'jango' => 'Jango Fett',
            'mace' => 'Mace Windu',
            'qui' => 'Qui-Gon Jinn',
            'dooku' => 'Count Dooku',
            'grievous' => 'General Grievous',
            'jarjar' => 'Jar Jar Binks',
            'wedge' => 'Wedge Antilles',
            'admiral' => 'Admiral Ackbar',
            'holdo' => 'Vice Admiral Holdo',
            'snoke' => 'Supreme Leader Snoke',
            'kylo' => 'Kylo Ren',
            'phasma' => 'Captain Phasma',
            'hux' => 'General Hux',
            'rose' => 'Rose Tico',
            'saw' => 'Saw Gerrera',
            'jyn' => 'Jyn Erso',
            'cassian' => 'Cassian Andor',
            'k2so' => 'K-2SO',
            'chirrut' => 'Chirrut Îmwe',
            'baze' => 'Baze Malbus',
            'bodhi' => 'Bodhi Rook',
        ];

        $needle = mb_strtolower(trim($search));

        $filtered = collect($catalog)
            ->filter(function (string $label, string $value) use ($needle): bool {
                if ($needle === '') {
                    return true;
                }

                return str_contains(mb_strtolower($label), $needle)
                    || str_contains(mb_strtolower($value), $needle);
            });

        $offset = is_numeric($cursor) ? max(0, (int) $cursor) : 0;
        $slice = $filtered->slice($offset, $pageSize);
        $items = $slice
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
        $nextOffset = $offset + count($items);

        return [
            'items' => $items,
            'cursor' => $nextOffset < $filtered->count() ? (string) $nextOffset : null,
            'hasMore' => $nextOffset < $filtered->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function tenThousandOptions(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $options = [];

        for ($index = 1; $index <= 10_000; $index++) {
            $options['opt_'.$index] = 'Option '.str_pad((string) $index, 5, '0', STR_PAD_LEFT);
        }

        $cache = $options;

        return $cache;
    }
}
