<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Actions\Concerns;

use Closure;
use DOMDocument;
use DOMElement;
use Filament\Actions\Action as BaseAction;
use InvalidArgumentException;

trait CanRequireHoldConfirm
{
    protected int|Closure|null $holdConfirmDuration = null;

    protected string|Closure $holdConfirmSweep = 'right';

    protected int|Closure $holdConfirmReleaseDuration = 200;

    protected ?string $holdConfirmOriginalView = null;

    protected bool|Closure $holdConfirmThemed = true;

    public function holdConfirm(int|Closure $duration = 2000, string|Closure $sweep = 'right'): static
    {
        $this->holdConfirmDuration = $duration;
        $this->holdConfirmSweep = $sweep;

        if ($this->holdConfirmOriginalView === null) {
            $this->holdConfirmOriginalView = $this->getView();
        }

        $this->livewireClickHandlerEnabled(false);
        $this->view('filament-flex-fields::actions.hold-confirm');

        if ($this->evaluate($this->actionRounded) === null && $this->getColor() === 'danger') {
            $this->rounded('full');
        }

        return $this;
    }

    public function holdConfirmSweep(string|Closure $sweep): static
    {
        $this->holdConfirmSweep = $sweep;

        return $this;
    }

    public function holdConfirmReleaseDuration(int|Closure $duration): static
    {
        $this->holdConfirmReleaseDuration = $duration;

        return $this;
    }

    public function holdConfirmThemed(bool|Closure $themed = true): static
    {
        $this->holdConfirmThemed = $themed;

        return $this;
    }

    public function getHoldConfirmDuration(): ?int
    {
        $duration = $this->evaluate($this->holdConfirmDuration);

        return is_int($duration) ? $duration : null;
    }

    public function getHoldConfirmReleaseDuration(): int
    {
        $duration = $this->evaluate($this->holdConfirmReleaseDuration);

        return is_int($duration) ? $duration : 200;
    }

    public function getHoldConfirmSweep(): string
    {
        $sweep = (string) $this->evaluate($this->holdConfirmSweep);

        if (! in_array($sweep, ['right', 'left', 'up', 'down'], true)) {
            throw new InvalidArgumentException("Hold confirm sweep [{$sweep}] is not supported.");
        }

        return $sweep;
    }

    public function hasHoldConfirm(): bool
    {
        return $this->getHoldConfirmDuration() !== null;
    }

    public function isHoldConfirmThemed(): bool
    {
        return (bool) $this->evaluate($this->holdConfirmThemed);
    }

    public function getHoldConfirmPalette(): ?string
    {
        if (! $this->isHoldConfirmThemed()) {
            return null;
        }

        $color = $this->getColor();

        return match ($color) {
            'danger' => 'danger',
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public function getHoldConfirmTriggerClasses(): array
    {
        $classes = ['fff-hold-confirm-action'];

        if ($palette = $this->getHoldConfirmPalette()) {
            $classes[] = "fff-hold-confirm-action--palette-{$palette}";
        }

        return $classes;
    }

    public function getHoldConfirmMountHandler(): ?string
    {
        if (filled($this->getUrl())) {
            return null;
        }

        $handler = $this->getJsClickHandler();

        return blank($handler) ? null : $handler;
    }

    public function getHoldConfirmCompleteExpression(): string
    {
        if ($url = $this->getUrl()) {
            $urlExpression = json_encode($url, JSON_THROW_ON_ERROR);

            if ($this->shouldOpenUrlInNewTab()) {
                return "window.open({$urlExpression}, '_blank')";
            }

            return "window.location.href = {$urlExpression}";
        }

        $handler = $this->getHoldConfirmMountHandler();

        if (blank($handler)) {
            return '';
        }

        return '$wire.'.$handler;
    }

    public function renderHoldConfirmTriggerHtml(): string
    {
        /** @var BaseAction $clone */
        $clone = $this->getClone();

        if ($this->holdConfirmOriginalView !== null) {
            $clone->view($this->holdConfirmOriginalView);
        }

        $clone->livewireClickHandlerEnabled(false);

        $clone->extraAttributes([
            'class' => implode(' ', $this->getHoldConfirmTriggerClasses()),
            'type' => 'button',
        ], merge: true);

        return $clone->toHtml();
    }

    protected function wrapHoldConfirmButtonHtml(string $html): string
    {
        $document = new DOMDocument;
        $previousState = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        $button = null;

        foreach (['button', 'a'] as $tag) {
            $nodes = $document->getElementsByTagName($tag);

            if ($nodes->length > 0) {
                $button = $nodes->item(0);

                break;
            }
        }

        if (! $button instanceof DOMElement) {
            return $html;
        }

        $contentNodes = [];
        $loadingNodes = [];

        foreach (iterator_to_array($button->childNodes) as $node) {
            if ($node instanceof DOMElement && $this->isHoldConfirmLoadingNode($node)) {
                $loadingNodes[] = $node;

                continue;
            }

            if ($node->nodeType === XML_TEXT_NODE && trim($node->textContent ?? '') === '') {
                continue;
            }

            $contentNodes[] = $node;
        }

        while ($button->firstChild) {
            $button->removeChild($button->firstChild);
        }

        $button->setAttribute('data-fff-hold-layers-ready', 'true');
        $button->appendChild($this->makeHoldConfirmLayerElement(
            document: $document,
            class: 'fff-hold-confirm-action__overlay',
            slot: 'hold-confirm-overlay',
            contentNodes: $contentNodes,
            ariaHidden: true,
        ));
        $button->appendChild($this->makeHoldConfirmLayerElement(
            document: $document,
            class: 'fff-hold-confirm-action__base',
            slot: 'hold-confirm-base',
            contentNodes: $contentNodes,
        ));

        foreach ($loadingNodes as $loadingNode) {
            $button->appendChild($loadingNode);
        }

        return $document->saveHTML($button) ?: $html;
    }

    /**
     * @param  array<int, \DOMNode>  $contentNodes
     */
    protected function makeHoldConfirmLayerElement(
        DOMDocument $document,
        string $class,
        string $slot,
        array $contentNodes,
        bool $ariaHidden = false,
    ): DOMElement {
        $layer = $document->createElement('span');
        $layer->setAttribute('class', $class);
        $layer->setAttribute('data-slot', $slot);

        if ($ariaHidden) {
            $layer->setAttribute('aria-hidden', 'true');
            $layer->setAttribute('data-sweep', $this->getHoldConfirmSweep());
        }

        foreach ($contentNodes as $contentNode) {
            $layer->appendChild($contentNode->cloneNode(true));
        }

        return $layer;
    }

    protected function isHoldConfirmLoadingNode(DOMElement $element): bool
    {
        if ($element->hasAttribute('x-cloak')) {
            return true;
        }

        foreach ($element->attributes ?? [] as $attribute) {
            if (str_starts_with($attribute->name, 'wire:loading')) {
                return true;
            }
        }

        return false;
    }
}
