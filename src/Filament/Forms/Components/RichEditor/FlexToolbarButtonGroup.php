<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor;

use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Illuminate\Support\Js;

class FlexToolbarButtonGroup extends ToolbarButtonGroup
{
    public function toEmbeddedHtml(): string
    {
        $resolvedButtons = $this->getResolvedButtons();

        if (empty($resolvedButtons)) {
            return '';
        }

        $isTextual = $this->hasTextualButtons();
        $label = $this->getName();
        $firstButton = $resolvedButtons[0];
        $icon = $this->getIcon();

        $defaultContent = \Filament\Support\generate_icon_html($icon ?? $firstButton->getIcon(), alias: $icon ? null : $firstButton->getIconAlias())->toHtml();
        $defaultContentHtml = $defaultContent;

        $triggerEffectJs = parent::buildTriggerEffect($resolvedButtons, $defaultContent);
        $activeExpression = $this->buildGroupActiveExpression($resolvedButtons);
        $buttonsHtml = $this->buildButtonsHtml($resolvedButtons);

        $triggerAttributes = $this->getExtraAttributeBag()
            ->merge([
                'type' => 'button',
                'tabindex' => -1,
                'aria-label' => e($label),
                'aria-haspopup' => 'menu',
                'x-on:click' => 'open = !open',
                'x-bind:aria-expanded' => 'open',
                'x-bind:class' => '{ \'fi-active\': '.$activeExpression.' }',
                'x-tooltip' => '{ content: '.Js::from($label)->toHtml().', theme: \'fff-rich-editor\', arrow: false }',
            ], escape: false)
            ->class([
                'fi-fo-rich-editor-dropdown-tool-trigger',
            ]);

        $xData = e('{ open: false, triggerContent: '.Js::from($defaultContent)->toHtml().', menuStyle: \'\' }');
        $xEffect = e($triggerEffectJs);
        $xInit = e($this->buildMenuPositionInit());
        $wrapperClass = 'fi-fo-rich-editor-dropdown-tool'.($isTextual ? ' fi-fo-rich-editor-dropdown-tool-textual' : '');
        $chevronSvg = '<svg class="fi-fo-rich-editor-dropdown-tool-chevron" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M3 4.5 6 7.5l3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        ob_start(); ?>

        <div x-data="<?= $xData ?>"
             x-effect="<?= $xEffect ?>"
             x-init="<?= $xInit ?>"
             x-on:click.outside="open = false"
             x-on:keydown.escape.prevent="open = false"
             class="<?= $wrapperClass ?>">

            <button <?= $triggerAttributes->toHtml() ?>>
                <span x-html="triggerContent"><?= $defaultContentHtml ?></span>
                <?= $chevronSvg ?>
            </button>

            <div x-show="open" x-cloak x-transition
                 :style="menuStyle"
                 class="fi-fo-rich-editor-dropdown-tool-menu fff-rich-editor__toolbar-dropdown-menu"
                 role="menu">
                <?= $buttonsHtml ?>
            </div>
        </div>

        <?php return ob_get_clean();
    }

    protected function buildButtonsHtml(array $resolvedButtons): string
    {
        $isTextual = $this->hasTextualButtons();
        $html = '';

        foreach ($resolvedButtons as $button) {
            $activeExpression = $this->buildActiveExpression($button);
            $buttonLabel = $button->getLabel();

            $buttonAttributes = $button->getExtraAttributeBag()
                ->merge([
                    'tabindex' => -1,
                    'type' => 'button',
                    'role' => 'menuitem',
                    'aria-label' => e($buttonLabel),
                    'x-on:click' => $button->getJsHandler().'; open = false',
                    'x-bind:class' => '{ \'fi-active\': '.$activeExpression.' }',
                    ...($isTextual ? [] : [
                        'x-tooltip' => '{ content: '.Js::from($buttonLabel)->toHtml().', theme: \'fff-rich-editor\', arrow: false }',
                    ]),
                ], escape: false)
                ->class([
                    'fi-fo-rich-editor-dropdown-tool-option',
                ]);

            $iconHtml = \Filament\Support\generate_icon_html($button->getIcon(), alias: $button->getIconAlias())->toHtml();

            $content = $isTextual
                ? $iconHtml.' <span>'.e($buttonLabel).'</span>'
                : $iconHtml;

            $html .= '<button '.$buttonAttributes->toHtml().'>'.$content.'</button>';
        }

        return $html;
    }

    protected function buildMenuPositionInit(): string
    {
        return <<<'JS'
            const reposition = () => {
                if (! open) {
                    menuStyle = '';

                    return;
                }

                const trigger = $el.querySelector('.fi-fo-rich-editor-dropdown-tool-trigger');

                if (! trigger) {
                    return;
                }

                const rect = trigger.getBoundingClientRect();
                menuStyle = `position: fixed; left: ${rect.left}px; top: ${rect.bottom + 4}px; z-index: 50;`;
            };

            $watch('open', () => requestAnimationFrame(reposition));

            const toolbar = $el.closest('.fff-rich-editor__toolbar');

            if (toolbar) {
                toolbar.addEventListener('scroll', () => requestAnimationFrame(reposition), { passive: true });
            }

            window.addEventListener('resize', () => requestAnimationFrame(reposition), { passive: true });
            window.addEventListener('scroll', () => requestAnimationFrame(reposition), { passive: true });
            JS;
    }
}
