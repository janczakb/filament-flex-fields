<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardGroup;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;

class FormLayoutPlayground
{
    private const YACHT_IMAGE = 'https://images.unsplash.com/photo-1567899378495-47b050a8d528?auto=format&fit=crop&w=1600&h=400&q=80';

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'form_layout__name' => 'Azimut 55 Fly',
            'form_layout__slug' => 'azimut-55-fly',
            'form_layout__description' => 'Luxury flybridge yacht with panoramic views and premium finishes.',
            'form_layout__length' => 17,
            'form_layout__berths' => 6,
            'form_layout__country' => 'HR',
            'form_layout__phone' => '+385911234567',
            'form_layout__class' => 'motor',
            'form_layout__rating' => 4.5,
            'form_layout__public_listing' => true,
            'form_layout__instant_book' => false,
            'form_layout__notifications' => 'email',
            'form_layout__profile_name' => 'Alex Rivera',
            'form_layout__profile_email' => 'alex@wyachts.com',
            'form_layout__contact_phone' => '+48123456789',
            'form_layout__contact_country' => 'PL',
            'form_layout__visibility' => 'published',
            'form_layout__budget' => ['min' => 2500, 'max' => 12000],
            'form_layout__currency' => [
                'amount' => 250_000,
                'currency' => 'EUR',
            ],
            'form_layout__broker' => 'jane',
            'form_layout__notes' => 'Prefer weekend departures from Split.',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Modern form layouts')
                ->description('Full forms built without Filament Section, Grid, Fieldset or FusedGroup — using CoverCard, SegmentTabs, ItemCardGroup and ItemCardStack instead.')
                ->extraAttributes(['class' => 'fff-playground-section fff-form-layouts-playground'])
                ->schema([
                    ...$this->charterListingDemo(),
                    ...$this->settingsPanelDemo(),
                    ...$this->stackedCardsDemo(),
                    ...$this->bookingFlowDemo(),
                ]),
        ];
    }

    /**
     * @return list<Component>
     */
    protected function charterListingDemo(): array
    {
        return [
            View::make('filament-flex-fields::partials.playground.form-layout-demo-heading')
                ->viewData([
                    'title' => 'Charter listing',
                    'description' => 'CoverCard hero + SegmentTabs — tabbed editor without Filament Tabs chrome.',
                ]),
            ItemCardStack::make()
                ->stackGap('lg')
                ->extraAttributes(['class' => 'fff-form-layout fff-form-layout--wide'])
                ->schema([
                    CoverCard::make()
                        ->backgroundImage(self::YACHT_IMAGE)
                        ->backgroundColor('#0f172a')
                        ->ratio('21:9')
                        ->tone('light')
                        ->fullWidth()
                        ->topTitle('New listing')
                        ->topDescription('Charter season 2026')
                        ->footerTitle('Draft')
                        ->footerDescription('Last saved 2 min ago'),
                    SegmentTabs::make('Listing')
                        ->variant('ghost')
                        ->fullWidth()
                        ->tabs([
                            SegmentTab::make('Details')
                                ->icon(GravityIcon::Car)
                                ->schema([
                                    ItemCardStack::make()
                                        ->extraAttributes(['class' => 'fff-form-layout__fields'])
                                        ->columns(['default' => 1, 'md' => 2])
                                        ->schema([
                                            FlexTextInput::make('form_layout__name')
                                                ->label('Yacht name')
                                                ->required(),
                                            FlexTextInput::make('form_layout__slug')
                                                ->label('URL slug')
                                                ->prefix('/charter/'),
                                            FlexTextareaField::make('form_layout__description')
                                                ->label('Description')
                                                ->rows(3)
                                                ->columnSpanFull(),
                                            NumberStepper::make('form_layout__length')
                                                ->label('Length (m)')
                                                ->minValue(5)
                                                ->maxValue(80)
                                                ->step(1),
                                            NumberStepper::make('form_layout__berths')
                                                ->label('Berths')
                                                ->minValue(2)
                                                ->maxValue(20)
                                                ->step(1),
                                        ]),
                                ]),
                            SegmentTab::make('Location')
                                ->icon(GravityIcon::Globe)
                                ->schema([
                                    ItemCardStack::make()
                                        ->extraAttributes(['class' => 'fff-form-layout__fields'])
                                        ->columns(['default' => 1, 'md' => 2])
                                        ->schema([
                                            CountryField::make('form_layout__country')
                                                ->label('Flag / registry'),
                                            PhoneField::make('form_layout__phone')
                                                ->label('Contact phone'),
                                        ]),
                                ]),
                            SegmentTab::make('Presentation')
                                ->icon(GravityIcon::Star)
                                ->schema([
                                    ItemCardStack::make()
                                        ->extraAttributes(['class' => 'fff-form-layout__fields'])
                                        ->schema([
                                            ChoiceCards::make('form_layout__class')
                                                ->label('Yacht class')
                                                ->layout('media')
                                                ->variant('secondary')
                                                ->gridColumns(['default' => 1, 'sm' => 3])
                                                ->options([
                                                    'motor' => [
                                                        'label' => 'Motor yacht',
                                                        'description' => 'Flybridge & sport',
                                                        'icon' => GravityIcon::Car,
                                                    ],
                                                    'sailing' => [
                                                        'label' => 'Sailing',
                                                        'description' => 'Monohull & catamaran',
                                                        'icon' => GravityIcon::Sun,
                                                    ],
                                                    'mega' => [
                                                        'label' => 'Mega yacht',
                                                        'description' => '30 m+ crewed',
                                                        'icon' => GravityIcon::Briefcase,
                                                    ],
                                                ]),
                                            RatingField::make('form_layout__rating')
                                                ->label('Guest rating preview')
                                                ->max(5)
                                                ->stars(5),
                                        ]),
                                ]),
                        ]),
                ]),
        ];
    }

    /**
     * @return list<Component>
     */
    protected function settingsPanelDemo(): array
    {
        $channelOptions = [
            'email' => 'Email',
            'push' => 'Push',
            'both' => 'Email & push',
            'none' => 'Off',
        ];

        return [
            View::make('filament-flex-fields::partials.playground.form-layout-demo-heading')
                ->viewData([
                    'title' => 'Listing preferences',
                    'description' => 'ItemCardGroup — iOS-style settings list with switches and inline selects.',
                ]),
            ItemCardGroup::make('Publishing')
                ->description('Control how this listing appears to guests')
                ->headerStyle('outside')
                ->variant('secondary')
                ->separated()
                ->schema([
                    ItemCard::make('Public listing')
                        ->description('Visible in search and catalog')
                        ->icon(GravityIcon::Eye)
                        ->schema([
                            SwitchField::make('form_layout__public_listing')
                                ->inline()
                                ->size('sm'),
                        ]),
                    ItemCard::make('Instant book')
                        ->description('Guests can book without manual approval')
                        ->icon(GravityIcon::Thunderbolt)
                        ->schema([
                            SwitchField::make('form_layout__instant_book')
                                ->inline()
                                ->size('sm'),
                        ]),
                    ItemCard::make('Notifications')
                        ->description('New inquiries and booking updates')
                        ->icon(GravityIcon::Bell)
                        ->schema([
                            SelectField::make('form_layout__notifications')
                                ->options($channelOptions)
                                ->variant('item-card')
                                ->hiddenLabel(),
                        ]),
                ]),
        ];
    }

    /**
     * @return list<Component>
     */
    protected function stackedCardsDemo(): array
    {
        return [
            View::make('filament-flex-fields::partials.playground.form-layout-demo-heading')
                ->viewData([
                    'title' => 'Owner profile',
                    'description' => 'ItemCard form panels — icon + copy on top, fields full width below (item-card--form-panel).',
                ]),
            ItemCardStack::make()
                ->stackGap('md')
                ->columns(['default' => 1, 'sm' => 2])
                ->extraAttributes(['class' => 'fff-form-layout fff-form-layout--grid'])
                ->schema([
                    ItemCard::make('Profile')
                        ->description('How you appear to guests and brokers')
                        ->icon(GravityIcon::Person)
                        ->variant('outline')
                        ->standalone()
                        ->extraAttributes(['class' => 'item-card--form-panel'])
                        ->columns(1)
                        ->schema([
                            FlexTextInput::make('form_layout__profile_name')
                                ->label('Display name'),
                            FlexTextInput::make('form_layout__profile_email')
                                ->label('Email')
                                ->email(),
                        ]),
                    ItemCard::make('Contact')
                        ->description('Reachable phone and country')
                        ->icon(GravityIcon::Handset)
                        ->variant('outline')
                        ->standalone()
                        ->extraAttributes(['class' => 'item-card--form-panel'])
                        ->columns(1)
                        ->schema([
                            PhoneField::make('form_layout__contact_phone')
                                ->label('Phone'),
                            CountryField::make('form_layout__contact_country')
                                ->label('Country'),
                        ]),
                    ItemCard::make('Visibility')
                        ->description('Who can see this listing')
                        ->icon(GravityIcon::ShieldCheck)
                        ->variant('outline')
                        ->standalone()
                        ->extraAttributes(['class' => 'item-card--form-panel'])
                        ->columnSpanFull()
                        ->schema([
                            ChoiceCards::make('form_layout__visibility')
                                ->label('Status')
                                ->hiddenLabel()
                                ->variant('secondary')
                                ->indicator('none')
                                ->gridColumns(['default' => 1, 'sm' => 3])
                                ->options([
                                    'draft' => [
                                        'label' => 'Draft',
                                        'description' => 'Only you',
                                    ],
                                    'published' => [
                                        'label' => 'Published',
                                        'description' => 'Live in catalog',
                                    ],
                                    'archived' => [
                                        'label' => 'Archived',
                                        'description' => 'Hidden from search',
                                    ],
                                ]),
                        ]),
                ]),
        ];
    }

    /**
     * @return list<Component>
     */
    protected function bookingFlowDemo(): array
    {
        $histogram = [
            30, 74, 85, 36, 98, 86, 30, 55, 80, 95, 63, 68, 47, 76, 83, 50, 93, 56, 95, 30,
        ];

        return [
            View::make('filament-flex-fields::partials.playground.form-layout-demo-heading')
                ->viewData([
                    'title' => 'Charter inquiry',
                    'description' => 'Compact booking strip — flat banner, price range and broker pick in one flow.',
                ]),
            ItemCardStack::make()
                ->stackGap('lg')
                ->extraAttributes(['class' => 'fff-form-layout fff-form-layout--wide'])
                ->schema([
                    CoverCard::make()
                        ->backgroundGradient('linear-gradient(90deg, rgb(15 23 42) 0%, rgb(30 58 138) 50%, rgb(14 116 144) 100%)')
                        ->ratio('3:1')
                        ->tone('light')
                        ->fullWidth()
                        ->footerTitle('Week charter')
                        ->footerDescription('Split · 7 nights · Jul 12–19'),
                    ItemCardStack::make()
                        ->extraAttributes(['class' => 'fff-form-layout__fields'])
                        ->columns(['default' => 1, 'lg' => 2])
                        ->schema([
                            PriceRangeField::make('form_layout__budget')
                                ->label('Weekly budget')
                                ->min(500)
                                ->max(25000)
                                ->step(100)
                                ->prefix('€')
                                ->histogram($histogram),
                            CurrencyField::make('form_layout__currency')
                                ->label('Currency')
                                ->currencies(['EUR', 'USD', 'GBP', 'PLN'])
                                ->currency('EUR'),
                        ]),
                    UserSelect::make('form_layout__broker')
                        ->label('Preferred broker')
                        ->helperText('Assign a broker to follow up on this inquiry.')
                        ->options($this->mockUserOptions())
                        ->searchable(),
                    FlexTextareaField::make('form_layout__notes')
                        ->label('Special requests')
                        ->placeholder('Dietary needs, embarkation preferences, crew requests…')
                        ->rows(3),
                ]),
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, image?: string, verified: bool}>
     */
    protected function mockUserOptions(): array
    {
        return [
            'jane' => [
                'label' => 'Jane Cooper',
                'description' => 'jane.cooper@example.com',
                'image' => 'https://ui-avatars.com/api/?name=Jane+Cooper&background=3b82f6&color=fff&size=128',
                'verified' => true,
            ],
            'alex' => [
                'label' => 'Alex Rivera',
                'description' => 'alex.rivera@example.com',
                'verified' => false,
            ],
            'sam' => [
                'label' => 'Sam Chen',
                'description' => 'sam.chen@example.com',
                'image' => 'https://ui-avatars.com/api/?name=Sam+Chen&background=10b981&color=fff&size=128',
                'verified' => true,
            ],
        ];
    }
}
