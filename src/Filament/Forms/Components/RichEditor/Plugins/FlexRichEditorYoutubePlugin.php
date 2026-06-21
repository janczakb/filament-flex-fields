<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Actions\YoutubeAction;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichEditorTool;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\TipTapExtensions\Youtube;
use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\RichEditorGravityIcons;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\HasToolbarButtons;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Support\Facades\FilamentAsset;
use Tiptap\Core\Extension;

class FlexRichEditorYoutubePlugin implements HasToolbarButtons, RichContentPlugin
{
    public function __construct(
        protected FlexRichEditor $field,
    ) {}

    public static function make(FlexRichEditor $field): static
    {
        return new static($field);
    }

    /**
     * @return array<Extension>
     */
    public function getTipTapPhpExtensions(): array
    {
        return [
            new Youtube($this->field->getYoutubeExtensionOptions()),
        ];
    }

    /**
     * @return array<string>
     */
    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc(
                FlexFieldAssets::FLEX_RICH_EDITOR_YOUTUBE_EXTENSION_SCRIPT_ID,
                FilamentFlexFieldsPlugin::PACKAGE_NAME,
            ),
        ];
    }

    /**
     * @return array<RichEditorTool>
     */
    public function getEditorTools(): array
    {
        return [
            FlexRichEditorTool::make('youtube')
                ->label(__('filament-flex-fields::default.rich_editor.youtube.tool'))
                ->action(arguments: '{}')
                ->activeKey('youtube')
                ->icon(RichEditorGravityIcons::icon('youtube'))
                ->iconAlias('filament-flex-fields::flex-rich-editor.toolbar.youtube'),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [
            YoutubeAction::make(),
        ];
    }

    /**
     * @return array<string|array<string|array<string>>>
     */
    public function getEnabledToolbarButtons(): array
    {
        return ['youtube'];
    }

    /**
     * @return array<string>
     */
    public function getDisabledToolbarButtons(): array
    {
        return [];
    }
}
