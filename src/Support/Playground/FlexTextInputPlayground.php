<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class FlexTextInputPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'flex_text_input__basic' => 'Styled single-line input',
            'flex_text_input__email' => 'hi@siennahewitt.com',
            'flex_text_input__password' => 'secret-password',
            'flex_text_input__domain' => 'acme',
            'flex_text_input__slug' => 'my-product-name',
            'flex_text_input__secondary' => '',
            'flex_text_input__soft' => 'Saving this for later…',
            'flex_text_input__flat' => '',
            'flex_text_input__sm' => 'Small input',
            'flex_text_input__lg' => 'Large input with more presence',
            'flex_text_input__disabled' => 'Disabled value',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Flex text input')
                ->description('Styled TextInput with optional emoji picker, speech dictation, counter, clear, loading, and password strength. All native TextInput APIs remain available.')
                ->schema([
                    FlexTextInput::make('flex_text_input__basic')
                        ->label('Basic')
                        ->placeholder('Type something…')
                        ->maxLength(80)
                        ->characterCounter()
                        ->emojiPicker()
                        ->speechDictation(),

                    Grid::make(2)->schema([
                        FlexTextInput::make('flex_text_input__email')
                            ->label('Email')
                            ->email()
                            ->autocomplete('off')
                            ->prefixIcon(Heroicon::Envelope)
                            ->required()
                            ->verificationStatus('Verified 2 Jan, 2027')
                            ->hintIcon(Heroicon::InformationCircle, 'We use this address for login and order updates. It is never shared with third parties.'),

                        FlexTextInput::make('flex_text_input__password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->copyable()
                            ->passwordStrength()
                            ->emojiPicker(),
                    ]),

                    FlexTextInput::make('flex_text_input__domain')
                        ->label('Website')
                        ->prefix('https://')
                        ->suffix('.com')
                        ->speechDictation(),

                    FlexTextInput::make('flex_text_input__slug')
                        ->label('Slug')
                        ->placeholder('my-product-name')
                        ->maxLength(60)
                        ->characterCounter()
                        ->clearable()
                        ->live(debounce: 500)
                        ->validating(),

                    Grid::make(4)->schema([
                        FlexTextInput::make('flex_text_input__secondary')
                            ->label('Secondary')
                            ->variant('secondary')
                            ->emojiPicker(),

                        FlexTextInput::make('flex_text_input__soft')
                            ->label('Soft')
                            ->variant('soft')
                            ->placeholder('Saving this for later…')
                            ->speechDictation(),

                        FlexTextInput::make('flex_text_input__flat')
                            ->label('Flat')
                            ->variant('flat')
                            ->speechDictation(),

                        FlexTextInput::make('flex_text_input__sm')
                            ->label('Small')
                            ->size('sm'),
                    ]),

                    Grid::make(2)->schema([
                        FlexTextInput::make('flex_text_input__lg')
                            ->label('Large')
                            ->size('lg')
                            ->emojiPicker()
                            ->speechDictation(),

                        FlexTextInput::make('flex_text_input__disabled')
                            ->label('Disabled')
                            ->disabled()
                            ->emojiPicker()
                            ->speechDictation(),
                    ]),
                ]),
        ];
    }
}
