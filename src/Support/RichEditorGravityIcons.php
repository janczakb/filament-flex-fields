<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Filament\Support\Facades\FilamentIcon;

/**
 * Gravity UI icons for Filament RichEditor toolbars and FlexRichEditor panels.
 */
final class RichEditorGravityIcons
{
    /**
     * @return array<string, string>
     */
    public static function packageAliases(): array
    {
        return [
            'filament-flex-fields::flex-rich-editor.toolbar.clear-formatting' => self::icon('clear_formatting'),
            'filament-flex-fields::flex-rich-editor.toolbar.clear-content' => self::icon('clear_content'),
            'filament-flex-fields::flex-rich-editor.toolbar.fullscreen' => self::icon('fullscreen'),
            'filament-flex-fields::flex-rich-editor.toolbar.youtube' => self::icon('youtube'),
        ];
    }

    public static function register(): void
    {
        FilamentIcon::register(self::packageAliases());
    }

    public static function icon(string $key): string
    {
        $configured = config("filament-flex-fields.ui.flex_rich_editor_{$key}_icon");

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return match ($key) {
            'undo' => GravityIcon::ArrowRotateLeft,
            'redo' => GravityIcon::ArrowRotateRight,
            'bold' => GravityIcon::make('bold'),
            'italic' => GravityIcon::make('italic'),
            'underline' => GravityIcon::make('underline'),
            'strike' => GravityIcon::make('strikethrough'),
            'link' => GravityIcon::Link,
            'text_color' => GravityIcon::Palette,
            'h1' => GravityIcon::make('heading-1'),
            'h2' => GravityIcon::make('heading-2'),
            'h3' => GravityIcon::make('heading-3'),
            'blockquote' => GravityIcon::make('quote-close'),
            'code' => GravityIcon::Code,
            'code_block' => GravityIcon::make('curly-brackets'),
            'bullet_list' => GravityIcon::make('list-ul'),
            'ordered_list' => GravityIcon::make('list-ol'),
            'attach_files' => GravityIcon::make('picture'),
            'clear_formatting' => GravityIcon::make('eraser'),
            'clear_content' => GravityIcon::TrashBin,
            'fullscreen' => GravityIcon::make('chevrons-expand-up-right'),
            'youtube' => GravityIcon::make('circle-play'),
            default => GravityIcon::make($key),
        };
    }

    public static function iconForToolName(string $toolName): ?string
    {
        return match ($toolName) {
            'bold', 'italic', 'underline', 'strike', 'link', 'h1', 'h2', 'h3',
            'blockquote', 'code', 'undo', 'redo', 'textColor' => self::icon($toolName === 'textColor' ? 'text_color' : $toolName),
            'codeBlock' => self::icon('code_block'),
            'bulletList' => self::icon('bullet_list'),
            'orderedList' => self::icon('ordered_list'),
            'attachFiles' => self::icon('attach_files'),
            'alignStart' => GravityIcon::make('bars-ascending-align-left'),
            'alignCenter' => GravityIcon::make('bars-ascending-align-center'),
            'alignEnd' => GravityIcon::make('bars-ascending-align-right'),
            'alignJustify' => GravityIcon::make('text-align-justify'),
            'clearFormatting' => self::icon('clear_formatting'),
            'clearContent' => self::icon('clear_content'),
            'youtube' => self::icon('youtube'),
            default => null,
        };
    }
}
