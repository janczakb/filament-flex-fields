<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
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
    }

    public function relationship(string|Closure|null $name = null, string|Closure|null $titleAttribute = null, ?Closure $modifyQueryUsing = null, bool $ignoreRecord = false): static
    {
        $userModifier = $modifyQueryUsing;

        $modifyQueryUsing = function (SelectField $component, Builder $query, ?string $search = null) use ($userModifier): Builder {
            $query = $component->restrictRelationshipQueryColumns($query);

            if ($userModifier === null) {
                return $query;
            }

            return $component->evaluate($userModifier, [
                'query' => $query,
                'search' => $search,
            ]) ?? $query;
        };

        return parent::relationship($name, $titleAttribute, $modifyQueryUsing, $ignoreRecord);
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
