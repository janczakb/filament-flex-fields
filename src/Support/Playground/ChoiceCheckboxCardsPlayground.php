<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCheckboxCards;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ChoiceCheckboxCardsPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'choice_checkbox_cards__stack_checkbox' => ['cheese', 'mushrooms'],
            'choice_checkbox_cards__stack_check' => ['security', 'storage'],
            'choice_checkbox_cards__no_indicator' => ['starter'],
            'choice_checkbox_cards__grid_addons' => ['backups', 'monitoring'],
            'choice_checkbox_cards__media_icons' => ['content', 'analytics'],
            'choice_checkbox_cards__featured' => ['two_factor', 'encryption'],
            'choice_checkbox_cards__meta' => ['product_updates', 'security_alerts'],
            'choice_checkbox_cards__ripple' => ['github'],
            'choice_checkbox_cards__size_sm' => ['starter'],
            'choice_checkbox_cards__size_md' => ['starter'],
            'choice_checkbox_cards__size_lg' => ['starter'],
            'choice_checkbox_cards__variant_primary' => ['pro'],
            'choice_checkbox_cards__color_success' => ['analytics'],
            'choice_checkbox_cards__min_max' => ['cheese', 'mushrooms'],
            'choice_checkbox_cards__exact' => ['a', 'b'],
            'choice_checkbox_cards__disabled_options' => ['pro'],
            'choice_checkbox_cards__disabled' => ['starter'],
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
        return Section::make('Choice Checkbox Cards')
            ->description('Multi-select card group — layouts, indicators, sizes, variants, validation and ripple.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Section::make('Indicators')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'xl' => 2])
                            ->schema([
                                ChoiceCheckboxCards::make('choice_checkbox_cards__stack_checkbox')
                                    ->label('Stack · checkbox indicator')
                                    ->helperText('Default stack layout with square checkbox (indicator: checkbox)')
                                    ->options($this->featureOptions())
                                    ->indicator('checkbox')
                                    ->layout('stack'),
                                ChoiceCheckboxCards::make('choice_checkbox_cards__stack_check')
                                    ->label('Stack · check indicator')
                                    ->helperText('Circular check when selected, empty ring when not (indicator: check)')
                                    ->options([
                                        'security' => [
                                            'label' => 'Security',
                                            'description' => 'Real-time threat detection and prevention',
                                        ],
                                        'storage' => [
                                            'label' => 'Storage',
                                            'description' => 'Unlimited cloud storage for your files',
                                        ],
                                        'analytics' => [
                                            'label' => 'Analytics',
                                            'description' => 'Advanced reporting and insights',
                                        ],
                                    ])
                                    ->indicator('check')
                                    ->layout('stack'),
                                ChoiceCheckboxCards::make('choice_checkbox_cards__no_indicator')
                                    ->label('No indicator')
                                    ->helperText('Border-only selection (indicator: none)')
                                    ->options($this->planOptions())
                                    ->indicator('none')
                                    ->layout('stack'),
                            ]),
                    ]),
                Section::make('Layouts')
                    ->compact()
                    ->schema([
                        ChoiceCheckboxCards::make('choice_checkbox_cards__grid_addons')
                            ->label('Grid layout · add-ons')
                            ->helperText('layout(grid) · gridColumns · price · indicator(check)')
                            ->layout('grid')
                            ->variant('secondary')
                            ->gridColumns(['default' => 1, 'sm' => 3])
                            ->indicator('check')
                            ->options([
                                'backups' => [
                                    'label' => 'Backups',
                                    'description' => 'Automated daily backups',
                                    'price' => '$5.00',
                                    'price_suffix' => '/mo',
                                ],
                                'monitoring' => [
                                    'label' => 'Monitoring',
                                    'description' => '24/7 uptime monitoring',
                                    'price' => '$9.00',
                                    'price_suffix' => '/mo',
                                ],
                                'support' => [
                                    'label' => 'Support',
                                    'description' => 'Priority support channel',
                                    'price' => '$15.00',
                                    'price_suffix' => '/mo',
                                ],
                            ]),
                        Grid::make(['default' => 1, 'xl' => 2])
                            ->schema([
                                ChoiceCheckboxCards::make('choice_checkbox_cards__media_icons')
                                    ->label('Media layout · with icons')
                                    ->helperText('layout(media) · icons · indicator(none)')
                                    ->layout('media')
                                    ->indicator('none')
                                    ->gridColumns(['default' => 1, 'sm' => 2])
                                    ->options([
                                        'content' => [
                                            'label' => 'Content Management',
                                            'description' => 'Create, edit, and delete content',
                                            'icon' => GravityIcon::Cloud,
                                        ],
                                        'analytics' => [
                                            'label' => 'Analytics Access',
                                            'description' => 'View and export reports',
                                            'icon' => GravityIcon::ChartColumn,
                                        ],
                                        'users' => [
                                            'label' => 'User Administration',
                                            'description' => 'Manage team members and roles',
                                            'icon' => GravityIcon::Persons,
                                        ],
                                        'settings' => [
                                            'label' => 'Settings',
                                            'description' => 'Configure system preferences',
                                            'icon' => GravityIcon::Lock,
                                        ],
                                    ]),
                                ChoiceCheckboxCards::make('choice_checkbox_cards__featured')
                                    ->label('Featured layout · icon cards')
                                    ->helperText('layout(featured) · icon · badge · indicator(check)')
                                    ->layout('featured')
                                    ->variant('primary')
                                    ->indicator('check')
                                    ->options([
                                        'two_factor' => [
                                            'label' => '2FA',
                                            'description' => 'Two-factor authentication for all user accounts.',
                                            'icon' => GravityIcon::Lock,
                                            'badge' => 'Recommended',
                                            'badge_color' => 'success',
                                        ],
                                        'encryption' => [
                                            'label' => 'Encryption',
                                            'description' => 'End-to-end encryption for sensitive data.',
                                            'icon' => GravityIcon::ShieldCheck,
                                        ],
                                        'backup' => [
                                            'label' => 'Cloud Backup',
                                            'description' => 'Automatic cloud backups every hour.',
                                            'icon' => GravityIcon::CloudArrowUpIn,
                                        ],
                                    ]),
                            ]),
                        ChoiceCheckboxCards::make('choice_checkbox_cards__meta')
                            ->label('Grid · meta footer')
                            ->helperText('Rich options with meta text · indicator(check)')
                            ->layout('grid')
                            ->gridColumns(['default' => 1, 'md' => 3])
                            ->indicator('check')
                            ->options([
                                'product_updates' => [
                                    'label' => 'Product Updates',
                                    'description' => 'Weekly product updates and tips',
                                    'meta' => '4,200 subscribers',
                                ],
                                'security_alerts' => [
                                    'label' => 'Security Alerts',
                                    'description' => 'Critical security notifications',
                                    'meta' => '1,890 subscribers',
                                ],
                                'marketing' => [
                                    'label' => 'Marketing',
                                    'description' => 'Promotions and special offers',
                                    'meta' => '920 subscribers',
                                ],
                            ]),
                    ]),
                Section::make('Sizes · variants · color · ripple')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'lg' => 3])
                            ->schema([
                                ChoiceCheckboxCards::make('choice_checkbox_cards__size_sm')
                                    ->label('Size · sm')
                                    ->helperText('size(sm)')
                                    ->size(ControlSize::Sm)
                                    ->options($this->planOptions()),
                                ChoiceCheckboxCards::make('choice_checkbox_cards__size_md')
                                    ->label('Size · md')
                                    ->helperText('size(md) — default')
                                    ->size(ControlSize::Md)
                                    ->options($this->planOptions()),
                                ChoiceCheckboxCards::make('choice_checkbox_cards__size_lg')
                                    ->label('Size · lg')
                                    ->helperText('size(lg)')
                                    ->size(ControlSize::Lg)
                                    ->options($this->planOptions()),
                                ChoiceCheckboxCards::make('choice_checkbox_cards__variant_primary')
                                    ->label('Variant · primary')
                                    ->helperText('variant(primary)')
                                    ->variant('primary')
                                    ->options($this->planOptions()),
                                ChoiceCheckboxCards::make('choice_checkbox_cards__color_success')
                                    ->label('Color · success')
                                    ->helperText('color(success)')
                                    ->color('success')
                                    ->options([
                                        'analytics' => 'Analytics',
                                        'sso' => 'Single sign-on',
                                        'api' => 'API access',
                                    ]),
                            ]),
                        ChoiceCheckboxCards::make('choice_checkbox_cards__ripple')
                            ->label('With ripple')
                            ->helperText('ripple() · layout(grid) · indicator(checkbox)')
                            ->ripple()
                            ->layout('grid')
                            ->gridColumns(['default' => 1, 'sm' => 3])
                            ->indicator('checkbox')
                            ->options([
                                'github' => [
                                    'label' => 'GitHub',
                                    'description' => 'Connect your GitHub repositories',
                                ],
                                'slack' => [
                                    'label' => 'Slack',
                                    'description' => 'Send notifications to Slack',
                                ],
                                'linear' => [
                                    'label' => 'Linear',
                                    'description' => 'Sync issues with Linear',
                                ],
                            ]),
                    ]),
                Section::make('Validation')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'xl' => 2])
                            ->schema([
                                ChoiceCheckboxCards::make('choice_checkbox_cards__min_max')
                                    ->label('Min / max selections')
                                    ->helperText('minSelections(1) · maxSelections(3) · required')
                                    ->options($this->toppingOptions())
                                    ->minSelections(1)
                                    ->maxSelections(3)
                                    ->layout('stack')
                                    ->required(),
                                ChoiceCheckboxCards::make('choice_checkbox_cards__exact')
                                    ->label('Exact selections')
                                    ->helperText('exactSelections(2)')
                                    ->options([
                                        'a' => 'Option A',
                                        'b' => 'Option B',
                                        'c' => 'Option C',
                                        'd' => 'Option D',
                                    ])
                                    ->exactSelections(2)
                                    ->layout('grid')
                                    ->gridColumns(['default' => 2, 'md' => 4])
                                    ->indicator('none'),
                            ]),
                    ]),
                Section::make('Disabled')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'xl' => 2])
                            ->schema([
                                ChoiceCheckboxCards::make('choice_checkbox_cards__disabled_options')
                                    ->label('Disabled options')
                                    ->helperText('disabledOptions([enterprise])')
                                    ->options($this->planOptions())
                                    ->disabledOptions(['enterprise']),
                                ChoiceCheckboxCards::make('choice_checkbox_cards__disabled')
                                    ->label('Disabled field')
                                    ->helperText('->disabled()')
                                    ->options($this->planOptions())
                                    ->disabled(),
                            ]),
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
     * @return array<string, array<string, string>>
     */
    protected function featureOptions(): array
    {
        return [
            'security' => [
                'label' => 'Security',
                'description' => 'Real-time threat detection and prevention',
            ],
            'storage' => [
                'label' => 'Storage',
                'description' => 'Unlimited cloud storage for your files',
            ],
            'analytics' => [
                'label' => 'Analytics',
                'description' => 'Advanced reporting and insights',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function toppingOptions(): array
    {
        return [
            'cheese' => ['label' => 'Extra cheese', 'description' => 'Mozzarella blend'],
            'mushrooms' => ['label' => 'Mushrooms', 'description' => 'Fresh button mushrooms'],
            'pepperoni' => ['label' => 'Pepperoni', 'description' => 'Spicy slices'],
            'olives' => ['label' => 'Olives', 'description' => 'Black olives'],
        ];
    }
}
