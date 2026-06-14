<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class FlexVerificationCodePlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'verification_code__account_verify' => '4320',
            'verification_code__default' => '523456',
            'verification_code__gap_only' => '',
            'verification_code__four_by_four' => '',
            'verification_code__alphanumeric' => 'A1B2C3',
            'verification_code__auto_submit_method' => '',
            'verification_code__auto_submit_callback' => '',
            'verification_code__sm' => '',
            'verification_code__md' => '',
            'verification_code__lg' => '',
            'verification_code__required' => '',
            'verification_code__disabled' => '123456',
        ];
    }

    public function section(): Section
    {
        return Section::make('Verification Code')
            ->description('OTP-style segmented inputs with groups, separators, sizes, loading, auto-submit, and character sets.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                FlexVerificationCode::make('verification_code__account_verify')
                    ->hiddenLabel()
                    ->heading('Verify account')
                    ->description("We've sent a code to a****@gmail.com")
                    ->footer(__('filament-flex-fields::default.verification_code.footer_prompt'))
                    ->footerAction(
                        Action::make('resendVerificationCode')
                            ->label(__('filament-flex-fields::default.verification_code.resend'))
                            ->link()
                            ->action('resendVerificationCodeDemo'),
                    )
                    ->length(6)
                    ->groups([3, 3])
                    ->groupSeparator('-')
                    ->columnSpanFull(),
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 2])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        FlexVerificationCode::make('verification_code__default')
                            ->label('Default · 6 digits · 3-3 · dash')
                            ->length(6)
                            ->groups([3, 3])
                            ->groupSeparator('-')
                            ->required(),
                        FlexVerificationCode::make('verification_code__gap_only')
                            ->label('Gap only · no separator character')
                            ->length(6)
                            ->groups([3, 3])
                            ->groupSeparator(null),
                    ]),
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 2])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        FlexVerificationCode::make('verification_code__four_by_four')
                            ->label('8 digits · 4-4 · dash')
                            ->length(8)
                            ->groups([4, 4])
                            ->groupSeparator('-'),
                        FlexVerificationCode::make('verification_code__auto_submit_method')
                            ->label('Auto submit · Livewire method')
                            ->length(6)
                            ->groups([3, 3])
                            ->groupSeparator('-')
                            ->autoSubmitMethod('verifyVerificationCodeDemo')
                            ->loading()
                            ->helperText('Calls verifyVerificationCodeDemo($code) when complete.'),
                    ]),
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        FlexVerificationCode::make('verification_code__alphanumeric')
                            ->label('Alphanumeric · 6 chars')
                            ->length(6)
                            ->groups([3, 3])
                            ->groupSeparator('-')
                            ->allowedCharacters('alphanumeric'),
                        FlexVerificationCode::make('verification_code__auto_submit_callback')
                            ->label('Auto submit · submitUsing()')
                            ->length(6)
                            ->groups([3, 3])
                            ->groupSeparator('-')
                            ->loading()
                            ->submitUsing(function (string $code): void {
                                Notification::make()
                                    ->title('Verification code submitted')
                                    ->body($code)
                                    ->success()
                                    ->send();
                            })
                            ->helperText('Uses submitUsing() with live debounce when complete.'),
                        FlexVerificationCode::make('verification_code__required')
                            ->label('Required · single group')
                            ->length(4)
                            ->required(),
                    ]),
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        FlexVerificationCode::make('verification_code__sm')
                            ->label('Size · SM')
                            ->length(6)
                            ->groups([3, 3])
                            ->groupSeparator('-')
                            ->size(ControlSize::Sm),
                        FlexVerificationCode::make('verification_code__md')
                            ->label('Size · MD')
                            ->length(6)
                            ->groups([3, 3])
                            ->groupSeparator('-')
                            ->size(ControlSize::Md),
                        FlexVerificationCode::make('verification_code__lg')
                            ->label('Size · LG')
                            ->length(6)
                            ->groups([3, 3])
                            ->groupSeparator('-')
                            ->size(ControlSize::Lg),
                    ]),
                Grid::make(['default' => 1, 'sm' => 2])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        FlexVerificationCode::make('verification_code__disabled')
                            ->label('Disabled')
                            ->length(6)
                            ->groups([3, 3])
                            ->groupSeparator('-')
                            ->disabled(),
                        FlexVerificationCode::make('verification_code__disabled_when_complete')
                            ->label('Disabled when complete')
                            ->length(4)
                            ->disabled(fn (Get $get): bool => strlen((string) ($get('verification_code__disabled_when_complete') ?? '')) >= 4)
                            ->live()
                            ->helperText('->disabled(fn (Get $get) => …) with ->live()'),
                    ]),
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
