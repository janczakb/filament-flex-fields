<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ChoiceCardsPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'choice_cards__plans' => 'pro',
            'choice_cards__no_indicator' => 'pro',
            'choice_cards__delivery' => 'express',
            'choice_cards__payment' => 'visa',
            'choice_cards__featured' => 'starter',
            'choice_cards__disabled' => 'pro',
        ];
    }

    public function section(): Section
    {
        return Section::make('Choice Cards')
            ->description('HeroUI-style selectable cards. Rich options with label, description, price, icons and badges.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Grid::make(['default' => 1, 'xl' => 2])
                    ->schema([
                        ChoiceCards::make('choice_cards__plans')
                            ->label('Select a plan')
                            ->helperText('Choose the plan that suits your needs')
                            ->options($this->planOptions())
                            ->default('pro'),
                        ChoiceCards::make('choice_cards__no_indicator')
                            ->label('Select a plan')
                            ->helperText('Border-only selection without radio indicator')
                            ->options($this->planOptions())
                            ->indicator('none')
                            ->default('pro'),
                    ]),
                Section::make('Grid layout')
                    ->compact()
                    ->schema([
                        ChoiceCards::make('choice_cards__delivery')
                            ->label('Delivery method')
                            ->layout('media')
                            ->variant('secondary')
                            ->gridColumns(['default' => 1, 'sm' => 3])
                            ->options([
                                'standard' => [
                                    'label' => 'Standard',
                                    'description' => '4–10 business days',
                                    'price' => '$5.00',
                                    'icon' => GravityIcon::Box,
                                ],
                                'express' => [
                                    'label' => 'Express',
                                    'description' => '2–5 business days',
                                    'price' => '$16.00',
                                    'icon' => GravityIcon::Car,
                                ],
                                'super-fast' => [
                                    'label' => 'Super Fast',
                                    'description' => '1 business day',
                                    'price' => '$25.00',
                                    'icon' => GravityIcon::Rocket,
                                ],
                            ]),
                    ]),
                Section::make('Media layout')
                    ->compact()
                    ->schema([
                        ChoiceCards::make('choice_cards__payment')
                            ->label('Payment method')
                            ->layout('media')
                            ->gridColumns(['default' => 1, 'sm' => 2])
                            ->options([
                                'visa' => [
                                    'label' => '**** 0123',
                                    'description' => 'Exp. on 01/2026',
                                    'icon' => GravityIcon::CreditCard,
                                ],
                                'mastercard' => [
                                    'label' => '**** 8304',
                                    'description' => 'Exp. on 06/2028',
                                    'icon' => GravityIcon::CreditCard,
                                ],
                                'paypal' => [
                                    'label' => 'PayPal',
                                    'description' => 'Pay with PayPal',
                                    'icon' => GravityIcon::CircleDollar,
                                ],
                            ]),
                    ]),
                Section::make('Featured cards')
                    ->compact()
                    ->schema([
                        ChoiceCards::make('choice_cards__featured')
                            ->label('Choose your workspace plan')
                            ->layout('featured')
                            ->variant('primary')
                            ->options([
                                'starter' => [
                                    'label' => 'Starter',
                                    'description' => 'For freelancers and solo makers shipping fast.',
                                    'price' => '$12',
                                    'price_suffix' => 'per month',
                                    'icon' => GravityIcon::Cube,
                                    'badge' => 'Most popular',
                                    'badge_color' => 'success',
                                ],
                                'growth' => [
                                    'label' => 'Growth',
                                    'description' => 'For small teams scaling their product.',
                                    'price' => '$29',
                                    'price_suffix' => 'per month',
                                    'icon' => GravityIcon::Flame,
                                ],
                                'business' => [
                                    'label' => 'Business',
                                    'description' => 'For companies with advanced compliance needs.',
                                    'price' => '$59',
                                    'price_suffix' => 'per month',
                                    'icon' => GravityIcon::Briefcase,
                                ],
                            ]),
                    ]),
                Section::make('Disabled')
                    ->compact()
                    ->schema([
                        ChoiceCards::make('choice_cards__disabled')
                            ->label('Select a plan')
                            ->helperText('Plan changes are temporarily unavailable.')
                            ->options($this->planOptions())
                            ->disabled(),
                    ]),
            ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function planOptions(): array
    {
        return [
            'starter' => [
                'label' => 'Starter',
                'description' => 'For individuals and small projects',
                'price' => '$5',
                'price_suffix' => '/mo',
            ],
            'pro' => [
                'label' => 'Pro',
                'description' => 'For growing teams and businesses',
                'price' => '$15',
                'price_suffix' => '/mo',
            ],
            'enterprise' => [
                'label' => 'Enterprise',
                'description' => 'For large organizations at scale',
                'price' => '$45',
                'price_suffix' => '/mo',
            ],
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [$this->section()];
    }
}
