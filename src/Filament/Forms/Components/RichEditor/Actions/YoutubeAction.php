<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Actions;

use Bjanczak\FilamentFlexFields\Support\RichEditor\YoutubeEmbedUrlResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;

class YoutubeAction
{
    public static function make(): Action
    {
        return Action::make('youtube')
            ->label(__('filament-flex-fields::default.rich_editor.youtube.tool'))
            ->modalHeading(__('filament-flex-fields::default.rich_editor.youtube.modal.heading'))
            ->modalWidth(Width::Large)
            ->fillForm(fn (array $arguments): array => [
                'url' => $arguments['src'] ?? null,
            ])
            ->schema([
                TextInput::make('url')
                    ->label(__('filament-flex-fields::default.rich_editor.youtube.modal.url'))
                    ->inputMode('url')
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->required(),
            ])
            ->action(function (array $arguments, array $data, RichEditor $component): void {
                $url = trim((string) ($data['url'] ?? ''));

                if (! YoutubeEmbedUrlResolver::isValidYoutubeUrl($url)) {
                    return;
                }

                $component->runCommands(
                    [
                        EditorCommand::make(
                            'setYoutubeVideo',
                            arguments: [[
                                'src' => $url,
                            ]],
                        ),
                    ],
                    editorSelection: $arguments['editorSelection'],
                );
            });
    }
}
