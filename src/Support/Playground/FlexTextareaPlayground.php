<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class FlexTextareaPlayground
{
    /**
     * @return array<string, string>
     */
    public static function modelOptions(): array
    {
        return [
            'gpt-5.4' => 'GPT-5.4',
            'claude-4.6-opus' => 'Claude 4.6 Opus',
            'claude-4.6-sonnet' => 'Claude 4.6 Sonnet',
            'gemini-3.1-pro' => 'Gemini 3.1 Pro',
        ];
    }

    public static function resolveModelLabel(?string $modelKey): string
    {
        if (blank($modelKey)) {
            return 'No model selected';
        }

        return self::modelOptions()[$modelKey] ?? $modelKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'flex_textarea__basic' => 'Tell me something interesting about Laravel.',
            'flex_textarea__model' => 'claude-4.6-opus',
            'flex_textarea__counter_only' => 'Only character counter enabled — no toolbar actions.',
            'flex_textarea__secondary' => '',
            'flex_textarea__soft' => 'Saving this for later…',
            'flex_textarea__flat' => '',
            'flex_textarea__sm' => 'Small box, slightly larger text.',
            'flex_textarea__lg' => 'Large textarea with more room for longer messages.',
            'flex_textarea__disabled' => 'Disabled state',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Flex Textarea')
                ->description('Universal styled textarea extending Filament Textarea. Toolbar actions use Filament Action / ActionGroup — modals, dropdowns, submit.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    FlexTextareaField::make('flex_textarea__basic')
                        ->label('Message')
                        ->placeholder('What do you want to know?')
                        ->helperText('Attach file, pick a model from the pill dropdown, use the action menu, dictate, or send.')
                        ->maxLength(500)
                        ->characterCounter()
                        ->speechDictation()
                        ->speechDictationLanguage('pl-PL')
                        ->emojiPicker()
                        ->emojiPickerLocale('pl')
                        ->footer('AI can make mistakes. Check important info.')
                        ->toolbarSelect(
                            'flex_textarea__model',
                            self::modelOptions(),
                            GravityIcon::Globe,
                            'Model',
                        )
                        ->toolbarActions([
                            Action::make('attach')
                                ->label('Attach file')
                                ->icon(GravityIcon::Paperclip)
                                ->iconButton()
                                ->action(fn () => Notification::make()
                                    ->title('Attach action')
                                    ->body('Use Action::make()->action() or ->schema() for modals.')
                                    ->info()
                                    ->send()),
                            ActionGroup::make([
                                Action::make('insertThanks')
                                    ->label('Insert thanks')
                                    ->icon(GravityIcon::FilePlus)
                                    ->action(fn () => Notification::make()
                                        ->title('Snippet inserted')
                                        ->body('Thanks for your message!')
                                        ->info()
                                        ->send()),
                                Action::make('clearMessage')
                                    ->label('Clear message')
                                    ->icon(GravityIcon::TrashBin)
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->action(fn () => Notification::make()
                                        ->title('Clear action')
                                        ->body('Wire this to reset the field state.')
                                        ->warning()
                                        ->send()),
                            ])
                                ->label('More actions')
                                ->icon(GravityIcon::Circles3Plus)
                                ->iconButton(),
                        ])
                        ->submitAction(
                            Action::make('send')
                                ->label('Send message')
                                ->icon(GravityIcon::ArrowUp)
                                ->iconButton()
                                ->color('primary')
                                ->action(function (Get $get): void {
                                    $modelLabel = self::resolveModelLabel($get('flex_textarea__model'));

                                    Notification::make()
                                        ->title('Message sent')
                                        ->body("Selected model: {$modelLabel}")
                                        ->success()
                                        ->send();
                                }),
                        )
                        ->columnSpanFull(),
                    FlexTextareaField::make('flex_textarea__counter_only')
                        ->label('Counter only')
                        ->placeholder('No toolbar — just animated autosize and character counter.')
                        ->maxLength(240)
                        ->characterCounter()
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            FlexTextareaField::make('flex_textarea__secondary')
                                ->label('Secondary')
                                ->variant('secondary')
                                ->placeholder('Secondary shell')
                                ->characterCounter()
                                ->maxLength(240)
                                ->submitAction(
                                    Action::make('send')
                                        ->label('Send message')
                                        ->icon(GravityIcon::ArrowUp)
                                        ->iconButton()
                                        ->color('primary')
                                        ->action(fn () => Notification::make()->title('Sent')->success()->send()),
                                ),
                            FlexTextareaField::make('flex_textarea__soft')
                                ->label('Soft')
                                ->variant('soft')
                                ->placeholder('Saving this for later…')
                                ->characterCounter()
                                ->maxLength(240)
                                ->submitAction(
                                    Action::make('send_soft')
                                        ->label('Send message')
                                        ->icon(GravityIcon::ArrowUp)
                                        ->iconButton()
                                        ->color('primary')
                                        ->action(fn () => Notification::make()->title('Sent')->success()->send()),
                                ),
                            FlexTextareaField::make('flex_textarea__flat')
                                ->label('Flat')
                                ->variant('flat')
                                ->placeholder('Flat variant without heavy chrome')
                                ->characterCounter(),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            FlexTextareaField::make('flex_textarea__sm')
                                ->label('Small')
                                ->size('sm')
                                ->placeholder('Small box, larger text')
                                ->characterCounter()
                                ->maxLength(120),
                            FlexTextareaField::make('flex_textarea__lg')
                                ->label('Large')
                                ->size('lg')
                                ->placeholder('Large textarea')
                                ->characterCounter()
                                ->maxLength(800)
                                ->toolbarActions([
                                    Action::make('attach')
                                        ->label('Attach file')
                                        ->icon(GravityIcon::Paperclip)
                                        ->iconButton()
                                        ->action(fn () => Notification::make()->title('Attach')->info()->send()),
                                ])
                                ->submitAction(
                                    Action::make('send')
                                        ->label('Send message')
                                        ->icon(GravityIcon::ArrowUp)
                                        ->iconButton()
                                        ->color('primary')
                                        ->action(fn () => Notification::make()->title('Sent')->success()->send()),
                                ),
                            FlexTextareaField::make('flex_textarea__disabled')
                                ->label('Disabled')
                                ->disabled()
                                ->placeholder('Disabled textarea'),
                        ]),
                ]),
        ];
    }
}
