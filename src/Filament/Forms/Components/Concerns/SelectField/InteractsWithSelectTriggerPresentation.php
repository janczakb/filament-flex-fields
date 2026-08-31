<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

/**
 * @mixin SelectField
 */
trait InteractsWithSelectTriggerPresentation
{
    public function getInitialTriggerLabel(): ?string
    {
        if ($this->isNative() || $this->getVariant() === 'item-card' || $this->isMultiple()) {
            return null;
        }

        return $this->resolveInitialTriggerLabel();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getInitialTriggerBadges(): array
    {
        if ($this->isNative() || $this->getVariant() === 'item-card' || ! $this->isMultiple()) {
            return [];
        }

        $state = $this->resolveStateForItemCardTrigger();

        if (! is_array($state) || $state === []) {
            return [];
        }

        if ($this->hasDynamicOptions() && ! $this->isPreloaded()) {
            $badges = [];

            foreach ($state as $value) {
                if ($value instanceof BackedEnum) {
                    $value = $value->value;
                }

                $badges[] = [
                    'value' => (string) $value,
                    'label' => (string) $value,
                ];
            }

            return $badges;
        }

        $badges = [];
        $options = $this->getOptions();

        foreach ($state as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $label = $this->findOptionLabel($options, $value);

            if (is_array($label)) {
                $badges[] = [
                    'value' => (string) $value,
                    'label' => $this->formatOptionLabelForJs($this->normalizeOption($value, $label), compact: true),
                ];

                continue;
            }

            $badges[] = [
                'value' => (string) $value,
                'label' => is_string($label) ? $label : (string) $value,
            ];
        }

        return $badges;
    }

    protected function resolveInitialTriggerLabel(): string
    {
        $state = $this->resolveStateForItemCardTrigger();

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        if (blank($state)) {
            return (string) ($this->getPlaceholder() ?? '');
        }

        if ($this->hasDynamicOptions() && ! $this->isPreloaded()) {
            return (string) $state;
        }

        $label = $this->findOptionLabel($this->getOptions(), $state);

        if ($label === null) {
            return (string) $state;
        }

        if (is_array($label)) {
            $normalized = $this->normalizeOption($state, $label);

            if ($this->getOptionLayout() === 'grid') {
                return $this->renderRichOptionLabel($normalized, layout: 'trigger');
            }

            if ($this->usesRichOptionHtml()) {
                return $this->formatOptionLabelForJs(
                    $normalized,
                    compact: ! $this->shouldUseRichListTriggerDisplay(),
                );
            }

            return $this->formatOptionLabelForJs($normalized, compact: true);
        }

        return (string) $label;
    }

    public function getItemCardInitialTriggerLabel(): ?string
    {
        if ($this->getVariant() !== 'item-card') {
            return null;
        }

        return $this->resolveInitialTriggerLabel();
    }

    protected function resolveStateForItemCardTrigger(): mixed
    {
        try {
            $state = $this->getState();
        } catch (\Throwable) {
            $state = null;
        }

        if ($state === null || ($state === '' && ! is_array($state))) {
            return $this->getDefaultState();
        }

        return $state;
    }

    protected function isLocallyDisabled(): bool
    {
        return (bool) $this->evaluate($this->isDisabled);
    }

    protected function hasSelectedValueForClearButton(): bool
    {
        $state = $this->resolveStateForItemCardTrigger();

        if (is_array($state)) {
            return $state !== [];
        }

        return filled($state);
    }

    public function getOptionLabel(bool $withDefault = true): ?string
    {
        $state = $this->getState();

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        $label = $this->findOptionLabel($this->getOptions(), $state);

        if ($label === null) {
            return parent::getOptionLabel($withDefault);
        }

        if (is_array($label)) {
            $normalized = $this->normalizeOption($state, $label);

            if ($this->getOptionLayout() === 'grid') {
                return $this->renderRichOptionLabel($normalized, layout: 'trigger');
            }

            return $this->formatOptionLabelForJs($normalized);
        }

        return (string) $label;
    }

    /**
     * @param  array<mixed>|null  $options
     * @return array<string, string>
     */
    public function getOptionLabels(bool $withDefaults = true, ?array $options = null): array
    {
        if ($this->getOptionLabelsUsing) {
            // We unconditionally pass $options to support Filament beta signatures.
            // In older versions, PHP silently ignores the extra userland argument.
            /** @phpstan-ignore-next-line */
            return parent::getOptionLabels($withDefaults, $options);
        }

        $labels = [];
        $options ??= $this->getOptions();

        foreach ($this->getState() ?? [] as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $label = $this->findOptionLabel($options, $value);

            if (is_array($label)) {
                $labels[$value] = $this->formatOptionLabelForJs($this->normalizeOption($value, $label), compact: true);

                continue;
            }

            if (is_string($label)) {
                $labels[$value] = $label;

                continue;
            }

            if ($withDefaults) {
                $labels[$value] = (string) $value;
            }
        }

        return $labels;
    }

    /**
     * @param  array<string | int, string | array<string, mixed>>  $options
     */
    protected function findOptionLabel(array $options, mixed $state): array|string|null
    {
        foreach ($options as $value => $label) {
            if (is_array($label) && $this->isOptionGroupArray($label)) {
                $found = $this->findOptionLabel($label, $state);

                if ($found !== null) {
                    return $found;
                }

                continue;
            }

            if ((string) $value === (string) $state) {
                return $label;
            }
        }

        return null;
    }
}
