<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor;

use Filament\Forms\Components\RichEditor\RichEditorTool;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Js;
use ReflectionProperty;

use function Filament\Support\generate_icon_html;

class FlexRichEditorTool extends RichEditorTool
{
    public function toEmbeddedHtml(): string
    {
        $activeJsExpression = $this->getActiveJsExpression();

        if (filled($activeJsExpression)) {
            $activeJsExpression = "editorUpdatedAt && ({$activeJsExpression})";
        } else {
            $activeJsExpression = 'editorUpdatedAt && $getEditor()?.isActive('.Js::from($this->getActiveKey())->toHtml().', '.Js::from($this->getActiveOptions())->toHtml().')';
        }

        $label = $this->getLabel();
        $isLabelHidden = $this->isLabelHidden();

        $attributes = $this->getExtraAttributeBag()
            ->merge([
                'tabindex' => -1,
                'type' => 'button',
                'aria-label' => e($label),
                'x-bind:class' => '{ \'fi-active\': '.($this->hasActiveStyling() ? $activeJsExpression : 'false').' }',
                'x-bind:disabled' => $this->isDisabledWhenNotActive() ? '!('.$activeJsExpression.')' : null,
                'x-on:click' => $this->getJsHandler(),
                'x-tooltip' => (filled($label) && $isLabelHidden)
                    ? '{ content: '.Js::from($label).', theme: \'fff-rich-editor\', arrow: false }'
                    : null,
            ], escape: false)
            ->class([
                'fi-fo-rich-editor-tool',
                'fi-fo-rich-editor-tool-with-label' => ! $isLabelHidden,
            ]);

        ob_start(); ?>

        <button <?= $attributes->toHtml() ?>>
            <?= generate_icon_html($this->getIcon(), alias: $this->getIconAlias())->toHtml() ?>
            <?= $isLabelHidden ? null : '<span class="fi-fo-rich-editor-tool-label">'.e($label).'</span>' ?>
        </button>

        <?php return ob_get_clean();
    }

    public static function fromParentTool(RichEditorTool $tool): static
    {
        if ($tool instanceof static) {
            return $tool;
        }

        $flex = static::make($tool->getName())
            ->icon(self::copyUnevaluatedProperty($tool, 'icon') ?? $tool->getIcon())
            ->iconAlias(self::copyUnevaluatedProperty($tool, 'iconAlias') ?? $tool->getIconAlias());

        $jsHandler = self::copyUnevaluatedProperty($tool, 'jsHandler');

        if ($jsHandler !== null) {
            $flex->jsHandler($jsHandler);
        }

        $activeJsExpression = self::copyUnevaluatedProperty($tool, 'activeJsExpression');

        if ($activeJsExpression !== null) {
            $flex->activeJsExpression($activeJsExpression);
        }

        $activeKey = self::copyUnevaluatedProperty($tool, 'activeKey');

        if ($activeKey !== null) {
            $flex->activeKey($activeKey);
        }

        $activeOptions = self::copyUnevaluatedProperty($tool, 'activeOptions');

        if ($activeOptions !== null) {
            $flex->activeOptions($activeOptions);
        }

        $flex
            ->disabledWhenNotActive($tool->isDisabledWhenNotActive())
            ->activeStyling($tool->hasActiveStyling());

        $label = $tool->getLabel();

        if ($label instanceof Htmlable || filled($label)) {
            $flex->label($label);
        }

        foreach ($tool->getExtraAttributes() as $attribute => $value) {
            $flex->extraAttributes([$attribute => $value], merge: true);
        }

        return $flex;
    }

    private static function copyUnevaluatedProperty(RichEditorTool $tool, string $property): mixed
    {
        $reflection = new ReflectionProperty(RichEditorTool::class, $property);

        return $reflection->getValue($tool);
    }
}
