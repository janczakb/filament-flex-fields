<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Closure;
use InvalidArgumentException;

/**
 * @mixin SelectField
 */
trait ConfiguresSelectPresentation
{
    protected string|Closure $variant = 'bordered';

    protected string|Closure|null $color = null;

    protected string|Closure $chipColor = 'neutral';

    protected bool|Closure $keepSelectedOptionsInDropdown = false;

    protected bool|Closure|null $usesRichOptions = null;

    protected string|Closure $optionLayout = 'list';

    protected bool|Closure $inlineFieldLabel = false;

    protected bool|Closure $inlineSearch = false;

    protected bool|Closure|null $richListTriggerDisplay = null;

    protected ?bool $usesRichOptionHtmlResolved = null;

    protected string|Closure|null $dropdownAlign = null;

    public function dropdownAlign(string|Closure $align): static
    {
        $this->dropdownAlign = $align;

        return $this;
    }

    public function getDropdownAlign(): string
    {
        if ($this->dropdownAlign !== null) {
            $align = (string) $this->evaluate($this->dropdownAlign);

            if (! in_array($align, ['start', 'end'], true)) {
                throw new InvalidArgumentException("Select dropdown align [{$align}] is not supported.");
            }

            return $align;
        }

        return $this->getVariant() === 'item-card' ? 'end' : 'start';
    }

    public function shouldUseRichListDropdownLayout(): bool
    {
        return $this->usesRichOptionHtml() && $this->getOptionLayout() === 'list';
    }

    /**
     * When true, the closed trigger keeps the full list HTML (icon + title + description).
     * SelectField defaults to compact trigger (icon + title) so field height matches size().
     * UserSelect overrides this to keep email/subtitle visible in the closed control.
     */
    public function shouldUseRichListTriggerDisplay(): bool
    {
        if ($this->richListTriggerDisplay !== null) {
            return (bool) $this->evaluate($this->richListTriggerDisplay);
        }

        return false;
    }

    /**
     * Keep the full list row HTML in the closed trigger (avatar + name + email).
     */
    public function richListTriggerDisplay(bool|Closure $condition = true): static
    {
        $this->richListTriggerDisplay = $condition;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function chipColor(string|Closure $chipColor): static
    {
        $this->chipColor = $chipColor;

        return $this;
    }

    /**
     * Keep selected values visible in the multi-select dropdown (checklist style)
     * instead of removing them from the option list after selection.
     */
    public function keepSelectedOptionsInDropdown(bool|Closure $condition = true): static
    {
        $this->keepSelectedOptionsInDropdown = $condition;

        return $this;
    }

    public function shouldKeepSelectedOptionsInDropdown(): bool
    {
        return $this->isMultiple() && (bool) $this->evaluate($this->keepSelectedOptionsInDropdown);
    }

    public function richOptions(bool|Closure $condition = true): static
    {
        $this->usesRichOptions = $condition;
        $this->forgetUsesRichOptionHtmlCache();

        return $this;
    }

    public function optionLayout(string|Closure $layout): static
    {
        $this->optionLayout = $layout;

        return $this;
    }

    public function inlineFieldLabel(bool|Closure $condition = true): static
    {
        $this->inlineFieldLabel = $condition;

        return $this;
    }

    public function hasInlineFieldLabel(): bool
    {
        return (bool) $this->evaluate($this->inlineFieldLabel);
    }

    public function inlineSearch(bool|Closure $condition = true): static
    {
        $this->inlineSearch = $condition;

        return $this;
    }

    public function hasInlineSearch(): bool
    {
        return $this->isSearchable()
            && ! $this->isMultiple()
            && (bool) $this->evaluate($this->inlineSearch);
    }

    public function getOptionLayout(): string
    {
        $layout = (string) $this->evaluate($this->optionLayout);

        if (! in_array($layout, ['list', 'grid'], true)) {
            throw new InvalidArgumentException("Select option layout [{$layout}] is not supported.");
        }

        return $layout;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['bordered', 'secondary', 'flat', 'faded', 'soft', 'underlined', 'item-card'], true)) {
            throw new InvalidArgumentException("Select variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function getColor(): ?string
    {
        $color = $this->evaluate($this->color);

        return filled($color) ? (string) $color : null;
    }

    public function getChipColor(): string
    {
        return (string) $this->evaluate($this->chipColor);
    }

    public function usesRichOptionHtml(): bool
    {
        if ($this->usesRichOptionHtmlResolved !== null) {
            return $this->usesRichOptionHtmlResolved;
        }

        if ($this->getOptionLayout() === 'grid') {
            return $this->usesRichOptionHtmlResolved = true;
        }

        if ($this->isHtmlAllowed()) {
            return $this->usesRichOptionHtmlResolved = true;
        }

        if ($this->hasOptionView()) {
            return $this->usesRichOptionHtmlResolved = true;
        }

        if ($this->usesRichOptions !== null) {
            return $this->usesRichOptionHtmlResolved = (bool) $this->evaluate($this->usesRichOptions);
        }

        return $this->usesRichOptionHtmlResolved = $this->optionsContainRichShape($this->getOptions());
    }

    protected function forgetUsesRichOptionHtmlCache(): void
    {
        $this->usesRichOptionHtmlResolved = null;
    }

    public function hasClientSideOptionList(): bool
    {
        if ($this->hasRelationship()) {
            return false;
        }

        if ($this->isPreloaded()) {
            return false;
        }

        if ($this->options instanceof Closure) {
            return false;
        }

        return true;
    }

    public function hasDynamicOptions(): bool
    {
        if ($this->hasClientSideOptionList()) {
            return false;
        }

        return parent::hasDynamicOptions();
    }

    public function hasInitialNoOptionsMessage(): bool
    {
        if ($this->hasClientSideOptionList()) {
            return false;
        }

        if ($this->options instanceof Closure) {
            return true;
        }

        return parent::hasInitialNoOptionsMessage();
    }

    /**
     * @return array<string, string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-select-field',
            'fff-select-field--'.$this->getSize(),
            'fff-rounding-'.$this->getRounding(),
            'fff-select-field--'.$this->getVariant(),
            'fff-select-field--layout-'.$this->getOptionLayout(),
            'fff-select-field--chips-'.$this->getChipColor(),
        ];

        if ($color = $this->getColor()) {
            $classes['fi-color-'.$color] = $color;
        }

        if ($this->hasInlineFieldLabel() && filled($this->getLabel()) && ! $this->isLabelHidden()) {
            $classes['fff-select-field--inline-field-label'] = true;
        }

        if ($this->hasInlineSearch()) {
            $classes['fff-select-field--inline-search'] = true;
        }

        if ($this->isMultiple()) {
            $classes['fff-select-field--multiple'] = true;
        }

        if (! $this->isClearable()) {
            $classes['fff-select-field--not-clearable'] = true;
        } elseif ($this->hasSelectedValueForClearButton() && ! $this->isLocallyDisabled()) {
            $classes['fff-select-field--clearable-has-value'] = true;
        }

        if ($this->shouldUseRichListTriggerDisplay()) {
            $classes['fff-select-field--rich-list-trigger'] = true;
        }

        if ($this->shouldShowFocusOutline()) {
            $classes['has-focus-outline'] = true;
        }

        return $classes;
    }

    public function getSearchableOptionFields(): array
    {
        $fields = parent::getSearchableOptionFields();

        if ($this->usesRichOptionHtml()) {
            $fields = array_values(array_unique([...$fields, 'description']));
        }

        return $fields;
    }
}
