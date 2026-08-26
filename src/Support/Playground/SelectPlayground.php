<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

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
            'select__multiple' => array_keys($this->genreOptions()),
            'select__multiple_checklist' => ['california', 'texas', 'delaware'],
            'select__multiple_sm' => ['action', 'adventure', 'drama', 'comedy'],
            'select__multiple_md' => ['action', 'adventure', 'drama', 'comedy'],
            'select__multiple_lg' => ['action', 'adventure', 'drama', 'comedy'],
            'select__grouped' => 'draft',
            'select__rich' => 'pro',
            'select__rich_icon_title' => 'pro',
            'select__rich_title_desc' => 'pro',
            'select__rich_sm' => 'pro',
            'select__rich_md' => 'pro',
            'select__rich_lg' => 'pro',
            'select__grid' => 'sky',
            'select__sm' => 'draft',
            'select__md' => 'reviewing',
            'select__lg' => 'published',
            'select__flat' => 'published',
            'select__faded' => 'published',
            'select__underlined' => 'published',
            'select__disabled' => 'published',
            'select__required' => null,
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

        $groupedOptions = [
            'In process' => [
                'draft' => 'Draft',
                'reviewing' => 'Reviewing',
            ],
            'Reviewed' => [
                'published' => 'Published',
                'rejected' => 'Rejected',
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

        return [
            Section::make('SelectField')
                ->description('Filament Select engine with premium SaaS design, rich options, searchable, grouped and multi-select chips.')
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
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            SelectField::make('select__multiple_sm')
                                ->label('Multiple chips · Small')
                                ->options($this->genreOptions())
                                ->multiple()
                                ->searchable()
                                ->chipColor('neutral')
                                ->size('sm'),
                            SelectField::make('select__multiple_md')
                                ->label('Multiple chips · Medium')
                                ->options($this->genreOptions())
                                ->multiple()
                                ->searchable()
                                ->chipColor('neutral'),
                            SelectField::make('select__multiple_lg')
                                ->label('Multiple chips · Large')
                                ->options($this->genreOptions())
                                ->multiple()
                                ->searchable()
                                ->chipColor('neutral')
                                ->size('lg'),
                        ]),
                    SelectField::make('select__grouped')
                        ->label('Grouped status')
                        ->options($groupedOptions)
                        ->searchable(),
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
                    SelectField::make('select__grid')
                        ->label('Theme picker (grid layout)')
                        ->options($themeOptions)
                        ->optionLayout('grid')
                        ->searchable()
                        ->richOptions(),
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
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
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
                        ]),
                    SelectField::make('select__disabled')
                        ->label('Disabled')
                        ->options($statusOptions)
                        ->disabled(),
                    SelectField::make('select__required')
                        ->label('Required status')
                        ->options($statusOptions)
                        ->required(),
                ]),
        ];
    }
}
