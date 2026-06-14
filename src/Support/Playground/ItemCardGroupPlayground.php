<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Actions\Action;
use Bjanczak\FilamentFlexFields\Filament\Actions\ActionGroup;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardGroup;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\IconPosition;

class ItemCardGroupPlayground
{
    private const DEMO_AVATAR = 'https://i.pravatar.cc/128?img=12';

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'icg__language' => true,
            'icg__theme' => true,
            'icg__dark_mode' => true,
            'icg__event_invites' => 'email',
            'icg__event_reminders' => 'push_whatsapp',
            'icg__marketing' => 'email',
            'icg__standalone_language' => 'en',
            'icg__standalone_dark_mode' => false,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $channelOptions = [
            'email' => 'Email',
            'push' => 'Push Notification',
            'whatsapp' => 'WhatsApp',
            'push_whatsapp' => 'Push Notification, WhatsApp',
            'none' => 'None',
        ];

        $languageOptions = [
            'en' => 'English',
            'pl' => 'Polish',
            'de' => 'German',
            'es' => 'Spanish',
        ];

        return [
            Section::make('Standalone Variants')
                ->description('Self-contained surfaces with variant styling when used outside ItemCardGroup.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    ItemCardStack::make()
                        ->schema([
                            ItemCard::make('Default')
                                ->description('Surface background with shadow')
                                ->icon(GravityIcon::Globe)
                                ->chevron(),
                            ItemCard::make('Secondary')
                                ->description('Secondary surface, no shadow')
                                ->icon(GravityIcon::Palette)
                                ->variant('secondary')
                                ->chevron(),
                            ItemCard::make('Tertiary')
                                ->description('Tertiary surface, no shadow')
                                ->icon(GravityIcon::Moon)
                                ->variant('tertiary')
                                ->chevron(),
                            ItemCard::make('Outline')
                                ->description('Transparent with border, no shadow')
                                ->icon(GravityIcon::Key)
                                ->variant('outline')
                                ->chevron(),
                        ]),
                ]),
            Section::make('Vertical Stack')
                ->description('Standalone pressable cards with spacing between each surface.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    ItemCardStack::make()
                        ->schema([
                            ItemCard::make('Profile')
                                ->description('Update your personal information')
                                ->icon(GravityIcon::Person)
                                ->chevron()
                                ->pressableAction(
                                    Action::make('standaloneOpenProfile')
                                        ->action(fn () => Notification::make()
                                            ->title('Profile opened')
                                            ->success()
                                            ->send()),
                                ),
                            ItemCard::make('Security')
                                ->description('Manage passwords and 2FA')
                                ->icon(GravityIcon::Key)
                                ->chevron()
                                ->pressableAction(
                                    Action::make('standaloneOpenSecurity')
                                        ->action(fn () => Notification::make()
                                            ->title('Security opened')
                                            ->success()
                                            ->send()),
                                ),
                            ItemCard::make('Cloud sync')
                                ->description('Sync settings across devices')
                                ->icon(GravityIcon::Cloud)
                                ->chevron()
                                ->pressableAction(
                                    Action::make('standaloneOpenCloudSync')
                                        ->action(fn () => Notification::make()
                                            ->title('Cloud sync opened')
                                            ->success()
                                            ->send()),
                                ),
                        ]),
                ]),
            Section::make('With Select')
                ->description('Standalone card with an item-card select trailing action.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    ItemCard::make('Language')
                        ->description('Choose your preferred language')
                        ->icon(GravityIcon::Globe)
                        ->schema([
                            SelectField::make('icg__standalone_language')
                                ->options($languageOptions)
                                ->variant('item-card')
                                ->hiddenLabel(),
                        ]),
                ]),
            Section::make('With Switch')
                ->description('Standalone card with an inline switch trailing action.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    ItemCard::make('Dark mode')
                        ->description('Use dark theme across the app')
                        ->icon(GravityIcon::Moon)
                        ->schema([
                            SwitchField::make('icg__standalone_dark_mode')
                                ->inline()
                                ->size('sm'),
                        ]),
                ]),
            Section::make('Title Only')
                ->description('Minimal standalone card with title and chevron.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    ItemCard::make('Appearance')
                        ->icon(GravityIcon::Palette)
                        ->chevron()
                        ->pressableAction(
                            Action::make('standaloneOpenAppearance')
                                ->action(fn () => Notification::make()
                                    ->title('Appearance opened')
                                    ->success()
                                    ->send()),
                        ),
                ]),
            Section::make('Without Icon')
                ->description('Standalone card with trailing action button and no leading icon.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    ItemCard::make('Delete account')
                        ->description('Permanently remove your account and all data')
                        ->schema([
                            Action::make('standaloneDeleteAccount')
                                ->label('Delete')
                                ->color('danger')
                                ->itemCard()
                                ->requiresConfirmation()
                                ->action(fn () => Notification::make()
                                    ->title('Delete requested')
                                    ->warning()
                                    ->send()),
                        ]),
                ]),
            Section::make('Leading Image')
                ->description('Use image() instead of icon(), or omit both for a text-only row.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    ItemCardStack::make()
                        ->schema([
                            ItemCard::make('Alex Rivera')
                                ->description('Rounded square crop (default icon shape)')
                                ->image(self::DEMO_AVATAR)
                                ->imageAlt('Alex Rivera')
                                ->chevron(),
                            ItemCard::make('Morgan Lee')
                                ->description('Circular avatar crop')
                                ->image(self::DEMO_AVATAR.'&u=2')
                                ->imageShape('circle')
                                ->imageAlt('Morgan Lee')
                                ->chevron(),
                            ItemCard::make('No leading visual')
                                ->description('Neither icon nor image')
                                ->chevron(),
                        ]),
                ]),
            Section::make('Pressable (Group)')
                ->description('Pressable rows inside ItemCardGroup — flat rows with shared surface.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    ItemCardGroup::make('Account')
                        ->description('Manage your account settings and preferences')
                        ->pressable()
                        ->separated()
                        ->schema([
                            ItemCard::make('Profile')
                                ->description('Update your personal information')
                                ->icon(GravityIcon::Person)
                                ->chevron()
                                ->pressableAction(
                                    Action::make('openProfile')
                                        ->action(fn () => Notification::make()
                                            ->title('Profile opened')
                                            ->success()
                                            ->send()),
                                ),
                            ItemCard::make('Security')
                                ->description('Manage your password and 2FA')
                                ->icon(GravityIcon::Key)
                                ->chevron()
                                ->pressableAction(
                                    Action::make('openSecurity')
                                        ->action(fn () => Notification::make()
                                            ->title('Security opened')
                                            ->success()
                                            ->send()),
                                ),
                            ItemCard::make('Cloud sync')
                                ->description('Sync settings across devices')
                                ->icon(GravityIcon::Cloud)
                                ->chevron()
                                ->pressableAction(
                                    Action::make('openCloudSync')
                                        ->action(fn () => Notification::make()
                                            ->title('Cloud sync opened')
                                            ->success()
                                            ->send()),
                                ),
                        ]),
                ]),
            Section::make('Variants')
                ->description('Surface styles and separated rows with chevron navigation.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    ItemCardGroup::make('Default')
                        ->description('Surface background with shadow')
                        ->pressable()
                        ->separated()
                        ->schema([
                            ItemCard::make('Profile')
                                ->description('Update your personal information')
                                ->icon(GravityIcon::Person)
                                ->chevron(),
                            ItemCard::make('Security')
                                ->description('Manage your password and 2FA')
                                ->icon(GravityIcon::Key)
                                ->chevron(),
                        ]),
                    ItemCardGroup::make('Secondary')
                        ->description('Secondary surface, no shadow')
                        ->variant('secondary')
                        ->separated()
                        ->schema([
                            ItemCard::make('Language')
                                ->description('Choose your preferred language')
                                ->icon(GravityIcon::Globe)
                                ->chevron(),
                            ItemCard::make('Appearance')
                                ->description('Customize theme and display')
                                ->icon(GravityIcon::Palette)
                                ->chevron(),
                        ]),
                    ItemCardGroup::make('Tertiary')
                        ->description('Tertiary surface, no separators')
                        ->variant('tertiary')
                        ->withoutSeparators()
                        ->schema([
                            ItemCard::make('Cloud sync')
                                ->description('Sync settings across devices')
                                ->icon(GravityIcon::Cloud)
                                ->chevron(),
                            ItemCard::make('Backup')
                                ->description('Automatic backup schedule')
                                ->icon(GravityIcon::Archive)
                                ->chevron(),
                        ]),
                    ItemCardGroup::make('Outline')
                        ->description('Transparent with border, no shadow')
                        ->variant('outline')
                        ->separated()
                        ->schema([
                            ItemCard::make('Devices')
                                ->description('Manage connected devices')
                                ->icon(GravityIcon::Smartphone)
                                ->chevron(),
                            ItemCard::make('Privacy')
                                ->description('Control your data and visibility')
                                ->icon(GravityIcon::ShieldCheck)
                                ->chevron(),
                        ]),
                ]),
            Section::make('ItemCardGroup')
                ->description('HeroUI item-card-group layout with mixed switch and item-card select actions.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    ItemCardGroup::make('Source Control')
                        ->headerStyle('outside')
                        ->separated()
                        ->schema([
                            ItemCard::make('GitHub')
                                ->description('Connected as @jrgarciadev on GitHub')
                                ->icon(GravityIcon::Code)
                                ->schema([
                                    ActionGroup::make([
                                        Action::make('outsideSyncRepositories')
                                            ->label('Sync repositories')
                                            ->action(fn () => Notification::make()
                                                ->title('Repositories synced')
                                                ->success()
                                                ->send()),
                                        Action::make('outsideDisconnectGitHub')
                                            ->label('Disconnect')
                                            ->color('danger')
                                            ->requiresConfirmation()
                                            ->action(fn () => Notification::make()
                                                ->title('GitHub disconnected')
                                                ->warning()
                                                ->send()),
                                    ])
                                        ->label('Manage')
                                        ->button()
                                        ->itemCard(),
                                ]),
                            ItemCard::make('GitLab')
                                ->description('Connect GitLab for repository management')
                                ->icon(GravityIcon::Server)
                                ->schema([
                                    Action::make('outsideConnectGitLab')
                                        ->label('Connect')
                                        ->icon(GravityIcon::ArrowUpRightFromSquare)
                                        ->iconPosition(IconPosition::After)
                                        ->itemCard()
                                        ->url('https://gitlab.com/users/sign_in', shouldOpenInNewTab: true),
                                ]),
                        ]),
                    ItemCardGroup::make('Settings')
                        ->description('Manage your account preferences and notification channels')
                        ->separated()
                        ->schema([
                            ItemCard::make('Language')
                                ->description('Enable automatic language detection')
                                ->icon(GravityIcon::Globe)
                                ->schema([
                                    SwitchField::make('icg__language')
                                        ->inline()
                                        ->size('sm')
                                        ->default(true),
                                ]),
                            ItemCard::make('Event Invites')
                                ->description('How you receive event invitations')
                                ->icon(GravityIcon::Envelope)
                                ->schema([
                                    SelectField::make('icg__event_invites')
                                        ->options($channelOptions)
                                        ->variant('item-card')
                                        ->hiddenLabel()
                                        ->default('email'),
                                ]),
                            ItemCard::make('Theme')
                                ->description('Follow your system appearance')
                                ->icon(GravityIcon::Palette)
                                ->schema([
                                    SwitchField::make('icg__theme')
                                        ->inline()
                                        ->size('sm')
                                        ->default(true),
                                ]),
                            ItemCard::make('Event Reminders')
                                ->description('Reminder delivery channels')
                                ->icon(GravityIcon::Bell)
                                ->schema([
                                    SelectField::make('icg__event_reminders')
                                        ->options($channelOptions)
                                        ->variant('item-card')
                                        ->hiddenLabel()
                                        ->default('push_whatsapp'),
                                ]),
                            ItemCard::make('Dark mode')
                                ->description('Override system theme on this device')
                                ->icon(GravityIcon::Moon)
                                ->schema([
                                    SwitchField::make('icg__dark_mode')
                                        ->inline()
                                        ->size('sm')
                                        ->default(true),
                                ]),
                            ItemCard::make('Marketing')
                                ->description('Promotional messages and updates')
                                ->icon(GravityIcon::Megaphone)
                                ->schema([
                                    SelectField::make('icg__marketing')
                                        ->options($channelOptions)
                                        ->variant('item-card')
                                        ->hiddenLabel()
                                        ->default('email'),
                                ]),
                            ItemCard::make('Hold confirm')
                                ->description('Hold the button until it fills to trigger the action')
                                ->icon(GravityIcon::Hand)
                                ->schema([
                                    Action::make('holdConfirmDefault')
                                        ->label('Update')
                                        ->color('primary')
                                        ->holdConfirm(2000)
                                        ->action(fn () => Notification::make()
                                            ->title('Settings updated')
                                            ->success()
                                            ->send()),
                                ]),
                            ItemCard::make('Hold confirm (fast)')
                                ->description('800ms hold, sweep from the right')
                                ->icon(GravityIcon::Hand)
                                ->schema([
                                    Action::make('holdConfirmFast')
                                        ->label('Hold to Delete')
                                        ->icon(GravityIcon::TrashBin)
                                        ->color('danger')
                                        ->holdConfirm(800)
                                        ->action(fn () => Notification::make()
                                            ->title('Fast hold confirmed')
                                            ->danger()
                                            ->send()),
                                ]),
                            ItemCard::make('Hold confirm (slow)')
                                ->description('4s hold, sweep from the left')
                                ->icon(GravityIcon::Hand)
                                ->schema([
                                    Action::make('holdConfirmSlow')
                                        ->label('Hold to Delete')
                                        ->icon(GravityIcon::TrashBin)
                                        ->color('danger')
                                        ->holdConfirm(4000, 'left')
                                        ->action(fn () => Notification::make()
                                            ->title('Slow hold confirmed')
                                            ->danger()
                                            ->send()),
                                ]),
                        ]),
                ]),
        ];
    }
}
