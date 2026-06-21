<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\InteractsWithFlexRichEditorEnhancements;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\InteractsWithFlexRichEditorFileAttachments;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\InteractsWithFlexRichEditorYoutube;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichEditorTool;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexToolbarButtonGroup;
use Bjanczak\FilamentFlexFields\Support\RichEditorGravityIcons;
use Closure;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

class FlexRichEditor extends RichEditor
{
    use HasControlSize;
    use HasFieldFocusOutline;
    use InteractsWithFlexRichEditorEnhancements;
    use InteractsWithFlexRichEditorFileAttachments;
    use InteractsWithFlexRichEditorYoutube;

    protected string $view = 'filament-flex-fields::forms.components.flex-rich-editor-field';

    protected string|Closure $variant = 'secondary';

    protected bool|Closure $showWordCount = false;

    protected bool|Closure $showJsonBadge = false;

    /**
     * @var list<string>
     */
    protected const VARIANTS = ['primary', 'secondary', 'soft', 'flat'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->json();

        $this->toolbarButtons($this->getFlexDefaultToolbarButtons());
        $this->floatingToolbars($this->getFlexDefaultFloatingToolbars());

        $this->tools(static::getFlexExtraTools());

        $this->registerFlexRichEditorFileAttachmentHooks();
        $this->registerFlexRichEditorEnhancementHooks();
    }

    /**
     * @return array<RichEditorTool>
     */
    public static function getFlexExtraTools(): array
    {
        return [
            FlexRichEditorTool::make('clearFormatting')
                ->label(__('filament-forms::components.rich_editor.tools.clear_formatting'))
                ->jsHandler('$getEditor()?.chain().focus().clearNodes().unsetAllMarks().run()')
                ->icon(RichEditorGravityIcons::icon('clear_formatting'))
                ->iconAlias('filament-flex-fields::flex-rich-editor.toolbar.clear-formatting'),
            FlexRichEditorTool::make('clearContent')
                ->label(__('filament-flex-fields::default.rich_editor.clear_content'))
                ->jsHandler('$getEditor()?.chain().focus().clearContent().run()')
                ->icon(RichEditorGravityIcons::icon('clear_content'))
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

            $gravityIcon = RichEditorGravityIcons::iconForToolName($name);

            if (is_string($gravityIcon) && $gravityIcon !== '') {
                $flexTool->icon($gravityIcon);
            }

            $flexTools[$name] = $flexTool->editor($this);
        }

        if ($this->shouldEnableRichEditorFullscreen()) {
            $flexTools['flexFullscreen'] = FlexRichEditorTool::make('flexFullscreen')
                ->label(__('filament-flex-fields::default.rich_editor.fullscreen.toggle'))
                ->jsHandler('toggleRichEditorFullscreen()')
                ->icon(RichEditorGravityIcons::icon('fullscreen'))
                ->iconAlias('filament-flex-fields::flex-rich-editor.toolbar.fullscreen')
                ->activeJsExpression('isRichEditorFullscreen')
                ->editor($this);
        }

        return $flexTools;
    }

    /**
     * @return array<array<string | ToolbarButtonGroup>>
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

    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-rich-editor-field',
            'fff-rich-editor-field--'.$this->getSize(),
            'fff-rich-editor-field--'.$this->getVariant(),
        ];

        if ($this->shouldShowFocusOutline()) {
            $classes[] = 'has-focus-outline';
        }

        if ($this->shouldEnableRichEditorDistractionFree()) {
            $classes[] = 'fff-rich-editor-field--distraction-free';
        }

        return $classes;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function wordCount(bool|Closure $condition = true): static
    {
        $this->showWordCount = $condition;

        return $this;
    }

    public function jsonBadge(bool|Closure $condition = true): static
    {
        $this->showJsonBadge = $condition;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = $this->evaluate($this->variant);

        if (! in_array($variant, self::VARIANTS, true)) {
            throw new InvalidArgumentException("Invalid rich editor variant [{$variant}].");
        }

        return $variant;
    }

    public function shouldShowWordCount(): bool
    {
        return (bool) $this->evaluate($this->showWordCount);
    }

    public function shouldShowJsonBadge(): bool
    {
        return (bool) $this->evaluate($this->showJsonBadge);
    }

    /**
     * @return list<string>
     */
    public function getDistractionFreeHiddenToolsForJs(): array
    {
        return $this->getDistractionFreeHiddenTools();
    }

    /**
     * @return array<RichEditorTool>
     */
    public static function getNativeExtraTools(): array
    {
        return [
            RichEditorTool::make('clearContent')
                ->label(__('filament-flex-fields::default.rich_editor.clear_content'))
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
                ToolbarButtonGroup::make(
                    __('filament-flex-fields::default.rich_editor.toolbar_groups.heading'),
                    ['h1', 'h2', 'h3'],
                ),
            ],
            [
                ToolbarButtonGroup::make(
                    __('filament-flex-fields::default.rich_editor.toolbar_groups.alignment'),
                    ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                ),
            ],
            ['blockquote', 'codeBlock'],
            [
                ToolbarButtonGroup::make(
                    __('filament-flex-fields::default.rich_editor.toolbar_groups.lists'),
                    ['bulletList', 'orderedList'],
                ),
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
            [
                FlexToolbarButtonGroup::make(
                    __('filament-flex-fields::default.rich_editor.toolbar_groups.heading'),
                    ['h1', 'h2', 'h3'],
                ),
            ],
            [
                FlexToolbarButtonGroup::make(
                    __('filament-flex-fields::default.rich_editor.toolbar_groups.alignment'),
                    ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                ),
            ],
            ['blockquote', 'codeBlock'],
            [
                FlexToolbarButtonGroup::make(
                    __('filament-flex-fields::default.rich_editor.toolbar_groups.lists'),
                    ['bulletList', 'orderedList'],
                ),
            ],
            ['link'],
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
                FlexToolbarButtonGroup::make(
                    __('filament-flex-fields::default.rich_editor.toolbar_groups.heading'),
                    ['h1', 'h2', 'h3'],
                ),
            ],
            [
                FlexToolbarButtonGroup::make(
                    __('filament-flex-fields::default.rich_editor.toolbar_groups.alignment'),
                    ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                ),
            ],
            ['blockquote', 'codeBlock'],
            [
                FlexToolbarButtonGroup::make(
                    __('filament-flex-fields::default.rich_editor.toolbar_groups.lists'),
                    ['bulletList', 'orderedList'],
                ),
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

    public function getJsonBadgeLabel(): string
    {
        return __('filament-flex-fields::default.field_types.json');
    }

    public function getWordCountEmptyLabel(): string
    {
        return __('filament-flex-fields::default.rich_editor.word_count.empty');
    }

    public function getWordCountLineTemplate(): string
    {
        $line = __('filament-flex-fields::default.rich_editor.word_count.line');

        return str_replace(
            [':characters', ':words'],
            ['__CHARACTERS__', '__WORDS__'],
            $line,
        );
    }
}
