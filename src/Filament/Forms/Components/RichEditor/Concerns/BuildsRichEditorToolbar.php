<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Concerns;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichEditorTool;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexToolbarButtonGroup;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\LocalizedToolbarButtonGroup;
use Bjanczak\FilamentFlexFields\Support\RichEditorFlexIcons;
use Bjanczak\FilamentFlexFields\Support\Translations;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use ReflectionProperty;

/** @mixin FlexRichEditor */
trait BuildsRichEditorToolbar
{
    protected function configureDefaultRichEditorTranslations(): void
    {
        // Filament constructs its default tool labels eagerly. Replace only those
        // defaults, before application tools are added, without changing vendor code.
        $labelProperty = new ReflectionProperty(RichEditorTool::class, 'label');

        foreach ($this->tools as $tool) {
            if (! $tool instanceof RichEditorTool) {
                continue;
            }

            // Native grid column tools intentionally reuse table column translations.
            $name = match ($tool->getName()) {
                'gridAddColumnBefore' => 'table_add_column_before',
                'gridAddColumnAfter' => 'table_add_column_after',
                'gridDeleteColumn' => 'table_delete_column',
                default => Str::snake($tool->getName()),
            };
            $key = 'filament-forms::components.rich_editor.tools.'.$name;
            $label = $labelProperty->getValue($tool);

            if (is_string($label) && $label === Translations::get($key)) {
                $tool->label(fn (): string => Translations::get($key));
            }
        }
    }

    /**
     * @return array<RichEditorTool>
     */
    public static function getFlexExtraTools(): array
    {
        return [
            FlexRichEditorTool::make('clearFormatting')
                ->label(fn () => Translations::get('filament-forms::components.rich_editor.tools.clear_formatting'))
                ->jsHandler('$getEditor()?.chain().focus().clearNodes().unsetAllMarks().run()')
                ->icon(RichEditorFlexIcons::icon('clear_formatting'))
                ->iconAlias('filament-flex-fields::flex-rich-editor.toolbar.clear-formatting'),
            FlexRichEditorTool::make('clearContent')
                ->label(fn () => Translations::get('filament-flex-fields::default.rich_editor.clear_content'))
                ->jsHandler('$getEditor()?.chain().focus().clearContent().run()')
                ->icon(RichEditorFlexIcons::icon('clear_content'))
                ->iconAlias('filament-flex-fields::flex-rich-editor.toolbar.clear-content'),
        ];
    }

    /**
     * @return array<string, RichEditorTool>
     */
    public function getTools(): array
    {
        $tools = parent::getTools();
        $flexTools = [];

        foreach ($tools as $name => $tool) {
            $flexTool = FlexRichEditorTool::fromParentTool($tool);

            $flexIcon = RichEditorFlexIcons::iconForToolName($name);

            if (is_string($flexIcon) && $flexIcon !== '') {
                $flexTool->icon($flexIcon)->iconAlias(null);
            }

            $flexTools[$name] = $flexTool->editor($this);
        }

        if ($this->shouldEnableRichEditorFullscreen()) {
            $flexTools['flexFullscreen'] = FlexRichEditorTool::make('flexFullscreen')
                ->label(fn () => Translations::get('filament-flex-fields::default.rich_editor.fullscreen.toggle'))
                ->jsHandler('toggleRichEditorFullscreen()')
                ->icon(RichEditorFlexIcons::icon('fullscreen'))
                ->iconAlias('filament-flex-fields::flex-rich-editor.toolbar.fullscreen')
                ->activeJsExpression('isRichEditorFullscreen')
                ->editor($this);
        }

        return $flexTools;
    }

    /**
     * @return array<int, string|array<int, string|ToolbarButtonGroup>>
     */
    public function getToolbarButtons(): array
    {
        $groups = parent::getToolbarButtons();

        if ($this->shouldEnableRichEditorFullscreen()) {
            $groups = $this->appendRichEditorFullscreenButton($groups);
        }

        return $this->filterDisabledRichEditorToolbarButtons($groups);
    }

    /**
     * @param  array<int, string|array<int, string|ToolbarButtonGroup>>  $groups
     * @return array<int, string|array<int, string|ToolbarButtonGroup>>
     */
    protected function appendRichEditorFullscreenButton(array $groups): array
    {
        $lastIndex = array_key_last($groups);

        if ($lastIndex === null) {
            return [['flexFullscreen']];
        }

        $lastGroup = $groups[$lastIndex];

        if (is_array($lastGroup)) {
            $groups[$lastIndex] = [...$lastGroup, 'flexFullscreen'];

            return $groups;
        }

        $groups[] = ['flexFullscreen'];

        return $groups;
    }

    /**
     * @return array<RichEditorTool>
     */
    public static function getNativeExtraTools(): array
    {
        return [
            RichEditorTool::make('clearContent')
                ->label(fn () => Translations::get('filament-flex-fields::default.rich_editor.clear_content'))
                ->jsHandler('$getEditor()?.chain().focus().clearContent().run()')
                ->icon(Heroicon::Trash),
        ];
    }

    /**
     * @return array<int, string|array<int, string|ToolbarButtonGroup>>
     */
    public function getNativeComparisonToolbarButtons(): array
    {
        return [
            ['undo', 'redo'],
            ['bold', 'italic', 'underline', 'strike', 'code'],
            [
                LocalizedToolbarButtonGroup::make('heading', ['h1', 'h2', 'h3'])
                    ->translatedName('filament-flex-fields::default.rich_editor.toolbar_groups.heading'),
            ],
            [
                LocalizedToolbarButtonGroup::make('alignment', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])
                    ->translatedName('filament-flex-fields::default.rich_editor.toolbar_groups.alignment'),
            ],
            ['blockquote', 'codeBlock'],
            [
                LocalizedToolbarButtonGroup::make('lists', ['bulletList', 'orderedList'])
                    ->translatedName('filament-flex-fields::default.rich_editor.toolbar_groups.lists'),
            ],
            ['link', 'attachFiles'],
            ['clearFormatting', 'clearContent'],
        ];
    }

    /**
     * @return array<int, string|array<int, string|FlexToolbarButtonGroup>>
     */
    public function getFlexDefaultToolbarButtons(): array
    {
        return [
            ['undo', 'redo'],
            ['bold', 'italic', 'underline', 'strike', 'code'],
            ['h1', 'h2', 'h3', 'blockquote', 'codeBlock'],
            ['bulletList', 'orderedList', 'link', 'attachFiles'],
            ['clearFormatting', 'clearContent'],
        ];
    }

    /**
     * @return array<int, string|array<int, string|FlexToolbarButtonGroup>>
     */
    public function getFlexFullToolbarButtons(): array
    {
        return [
            ['undo', 'redo'],
            ['bold', 'italic', 'underline', 'strike', 'code'],
            [
                FlexToolbarButtonGroup::make('heading', ['h1', 'h2', 'h3'])
                    ->translatedName('filament-flex-fields::default.rich_editor.toolbar_groups.heading'),
            ],
            [
                FlexToolbarButtonGroup::make('alignment', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])
                    ->translatedName('filament-flex-fields::default.rich_editor.toolbar_groups.alignment'),
            ],
            ['blockquote', 'codeBlock'],
            [
                FlexToolbarButtonGroup::make('lists', ['bulletList', 'orderedList'])
                    ->translatedName('filament-flex-fields::default.rich_editor.toolbar_groups.lists'),
            ],
            ['link', 'attachFiles'],
            ['clearFormatting', 'clearContent'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public function getFlexDefaultFloatingToolbars(): array
    {
        return [
            'paragraph' => [
                'bold',
                'italic',
                'underline',
                'strike',
                'link',
            ],
        ];
    }
}
