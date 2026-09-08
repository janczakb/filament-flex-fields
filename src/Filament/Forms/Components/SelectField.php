<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldRounding;
use Bjanczak\FilamentFlexFields\Concerns\InteractsWithRestrictedModelQueries;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\HasSelectFieldIcons;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField\ConfiguresSelectPresentation;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField\ConfiguresSelectSmartSuggest;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField\InteractsWithSelectAsyncSearch;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField\InteractsWithSelectHeadlessRuntime;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField\InteractsWithSelectTriggerPresentation;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField\RendersSelectOptionViews;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField\TransformsSelectRichOptions;
use Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Livewire\Attributes\Renderless;

class SelectField extends Select
{
    use ConfiguresSelectPresentation;
    use ConfiguresSelectSmartSuggest;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasFieldRounding;
    use HasSelectFieldIcons;
    use InteractsWithRestrictedModelQueries;
    use InteractsWithSelectAsyncSearch;
    use InteractsWithSelectHeadlessRuntime;
    use InteractsWithSelectTriggerPresentation;
    use RendersSelectOptionViews;
    use TransformsSelectRichOptions;

    protected string $view = 'filament-flex-fields::forms.components.select-field';

    protected bool|Closure|null $clearable = null;

    /**
     * @return array<string | array<string>>
     */
    public function getOptions(): array
    {
        if ($this->shouldDeferHeadlessOptionsUntilOpen() && ! $this->allowDeferredOptionResolution) {
            return [];
        }

        return parent::getOptions();
    }

    /**
     * @return array<array{'label': string, 'value': string}>
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getOptionsForJs(): array
    {
        $this->allowDeferredOptionResolution = true;

        try {
            return parent::getOptionsForJs();
        } finally {
            $this->allowDeferredOptionResolution = false;
        }
    }

    public function hasDynamicSearchResults(): bool
    {
        if ($this->getSearchResultsPageUsing instanceof Closure) {
            return true;
        }

        return parent::hasDynamicSearchResults();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->native(false);

        $this->transformOptionsForJsUsing(function (SelectField $component, array $options): array {
            return $component->transformRichOptionsForJs($options);
        });

        parent::selectablePlaceholder(function (SelectField $component): bool {
            return $component->isClearable();
        });

        $this->afterStateHydrated(function (SelectField $component): void {
            ObservabilityHooks::record(ObservabilityHooks::EVENT_FIELD_MOUNT, [
                'field' => $component->getName(),
                'type' => 'select',
            ]);
        });

        // Static options() only: reject POSTed keys outside the option list when
        // allowCreateOption() is off. Relationship / async search leave validation to Filament.
        $this->rule(function (SelectField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! $component->shouldEnforceStaticOptionKeys()) {
                    return;
                }

                if (blank($value)) {
                    return;
                }

                $allowed = $component->getStaticOptionKeysForValidation();
                $candidates = $component->isMultiple()
                    ? (is_array($value) ? $value : [$value])
                    : [$value];

                foreach ($candidates as $candidate) {
                    if ($candidate instanceof BackedEnum) {
                        $candidate = $candidate->value;
                    }

                    if (! in_array((string) $candidate, $allowed, true)) {
                        $fail(__('validation.in', ['attribute' => $component->getLabel()]));

                        return;
                    }
                }
            };
        });
    }

    /**
     * When inline create is enabled, created string keys are intentional state —
     * skip Filament’s “must resolve an option label” In probe.
     *
     * For static options() with create off, the package Closure on setUp owns
     * key enforcement — also skip the Filament probe so validation does not
     * require a Livewire container solely to build rules.
     *
     * @return ?array<string>
     */
    public function getInValidationRuleValues(): ?array
    {
        if ($this->allowsCreateOption() || $this->shouldEnforceStaticOptionKeys()) {
            return null;
        }

        return parent::getInValidationRuleValues();
    }

    /**
     * Package Rule::in-equivalent applies only to configured static options().
     */
    public function shouldEnforceStaticOptionKeys(): bool
    {
        if ($this->allowsCreateOption()) {
            return false;
        }

        if ($this->hasRelationship() || $this->hasDynamicSearchResults()) {
            return false;
        }

        if ($this->options === null && blank($this->getEnum())) {
            return false;
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function getStaticOptionKeysForValidation(): array
    {
        $this->allowDeferredOptionResolution = true;

        try {
            return $this->flattenStaticOptionKeys($this->getOptions());
        } finally {
            $this->allowDeferredOptionResolution = false;
        }
    }

    /**
     * @param  array<string|int, mixed>  $options
     * @return list<string>
     */
    protected function flattenStaticOptionKeys(array $options): array
    {
        $keys = [];

        foreach ($options as $key => $label) {
            if (! is_array($label)) {
                $keys[] = (string) $key;

                continue;
            }

            if ($this->isRichOptionArray($label)) {
                $keys[] = (string) $key;

                continue;
            }

            if ($this->isOptionGroupArray($label)) {
                foreach ($label as $childKey => $childLabel) {
                    $keys[] = (string) $childKey;
                }

                continue;
            }

            $keys[] = (string) $key;
        }

        return array_values(array_unique($keys));
    }

    public function relationship(string|Closure|null $name = null, string|Closure|null $titleAttribute = null, ?Closure $modifyQueryUsing = null, bool $ignoreRecord = false): static
    {
        return parent::relationship(
            $name,
            $titleAttribute,
            $this->wrapRelationshipQueryModifier($modifyQueryUsing),
            $ignoreRecord,
        );
    }

    /**
     * Fail fast when native(true) is combined with features the Blade view forces off
     * (`searchable`, `multiple`, or HTML) — see select-field.blade.php `$isNative` gate.
     */
    public function native(bool|Closure $condition = true): static
    {
        parent::native($condition);

        if (! $condition instanceof Closure && $condition) {
            $this->assertNativeSelectCompatibility();
        }

        return $this;
    }

    /**
     * @param  bool | array<string> | Closure  $condition
     */
    public function searchable(bool|array|Closure $condition = true): static
    {
        parent::searchable($condition);

        if ($this->isConcreteNativeSelectEnabled()) {
            if (is_array($condition) || $condition === true) {
                $this->throwNativeSelectIncompatibility('searchable()');
            }
        }

        return $this;
    }

    public function multiple(bool|Closure $condition = true): static
    {
        parent::multiple($condition);

        if ($this->isConcreteNativeSelectEnabled() && ! $condition instanceof Closure && $condition) {
            $this->throwNativeSelectIncompatibility('multiple()');
        }

        return $this;
    }

    public function allowHtml(bool|Closure $condition = true): static
    {
        parent::allowHtml($condition);

        if ($this->isConcreteNativeSelectEnabled() && ! $condition instanceof Closure && $condition) {
            $this->throwNativeSelectIncompatibility('allowHtml()');
        }

        return $this;
    }

    protected function isConcreteNativeSelectEnabled(): bool
    {
        return ! ($this->isNative instanceof Closure) && (bool) $this->isNative;
    }

    protected function assertNativeSelectCompatibility(): void
    {
        if ($this->isSearchable() || $this->isMultiple() || $this->isHtmlAllowed()) {
            $this->throwNativeSelectIncompatibility(
                'searchable(), multiple(), or allowHtml()',
            );
        }
    }

    protected function throwNativeSelectIncompatibility(string $feature): never
    {
        throw new InvalidArgumentException(
            "SelectField cannot use native(true) with {$feature}. Native selects only support a plain single-value list without search or HTML.",
        );
    }

    public function clearable(bool|Closure $condition = true): static
    {
        $this->clearable = $condition;

        parent::selectablePlaceholder($condition);

        return $this;
    }

    public function isClearable(): bool
    {
        if ($this->clearable !== null) {
            return (bool) $this->evaluate($this->clearable);
        }

        return $this->getVariant() !== 'item-card';
    }

    public function selectablePlaceholder(bool|Closure $condition = true): static
    {
        return parent::selectablePlaceholder($condition);
    }

    /**
     * Whether the clear (×) control and null-placeholder selection are available in the UI.
     * Combines Flex Fields `clearable()` with Filament `selectablePlaceholder()`.
     */
    public function isClearableInUi(): bool
    {
        return $this->isClearable() && $this->canSelectPlaceholder();
    }

    /**
     * Resolve options from parent field value(s). Parent path(s) should be `->live()`.
     *
     * Prefer `->live()->skipRenderAfterStateUpdated()` (or
     * `partiallyRenderComponentsAfterStateUpdated([...])`) on the parent so a
     * large form does not remorph for seconds and freeze sibling triggers.
     * This field keeps `wire:ignore` and loads options via `getOptionsForJs` when opened.
     *
     * @param  string|list<string>  $paths
     * @param  Closure(mixed ...$parentValues): array<array-key, mixed>  $resolveOptions
     */
    public function dependsOn(string|array $paths, Closure $resolveOptions): static
    {
        $paths = array_values(Arr::wrap($paths));

        return $this->options(function (Get $get) use ($paths, $resolveOptions): array {
            $values = [];

            foreach ($paths as $path) {
                $values[] = $get($path);
            }

            $resolved = $resolveOptions(...$values);

            return is_array($resolved) ? $resolved : [];
        });
    }
}
