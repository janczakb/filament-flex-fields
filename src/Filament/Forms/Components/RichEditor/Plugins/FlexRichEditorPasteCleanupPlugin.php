<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins;

use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Support\Facades\FilamentAsset;
use Tiptap\Core\Extension;

class FlexRichEditorPasteCleanupPlugin implements RichContentPlugin
{
    public function __construct(
        protected string $mode = 'standard',
    ) {}

    public static function make(string $mode = 'standard'): static
    {
        return new static(mode: $mode);
    }

    /**
     * @return array<Extension>
     */
    public function getTipTapPhpExtensions(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc(
                FlexFieldAssets::FLEX_RICH_EDITOR_PASTE_EXTENSION_SCRIPT_ID,
                FilamentFlexFieldsPlugin::PACKAGE_NAME,
            ),
        ];
    }

    /**
     * @return array<RichEditorTool>
     */
    public function getEditorTools(): array
    {
        return [];
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [];
    }

    public function getMode(): string
    {
        return $this->mode;
    }
}
