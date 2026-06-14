<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Bjanczak\FilamentFlexFields\Support\Slug\SlugGenerator;
use Bjanczak\FilamentFlexFields\Support\Slug\SpatieSlugIntegration;
use Closure;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;

trait GeneratesSlugFromSource
{
    protected ?Closure $slugifyUsing = null;

    protected string|Closure|null $spatieModel = null;

    protected string|Closure $spatieSlugField = 'slug';

    protected string|Closure|null $spatieSourceField = null;

    protected string|Closure $slugSeparator = '-';

    protected int|Closure|null $maxSlugLength = null;

    protected string|Closure|null $recordSlug = null;

    protected bool|Closure $serverSideGeneration = false;

    public function slugifyUsing(?Closure $callback): static
    {
        $this->slugifyUsing = $callback;

        return $this;
    }

    public function spatieModel(string|Closure|null $modelClass): static
    {
        $this->spatieModel = $modelClass;

        return $this;
    }

    public function spatieSlugField(string|Closure $attribute = 'slug'): static
    {
        $this->spatieSlugField = $attribute;

        return $this;
    }

    public function spatieSourceField(string|Closure|null $field): static
    {
        $this->spatieSourceField = $field;

        return $this;
    }

    public function slugSeparator(string|Closure $separator = '-'): static
    {
        $this->slugSeparator = $separator;

        return $this;
    }

    public function maxSlugLength(int|Closure|null $length): static
    {
        $this->maxSlugLength = $length;

        return $this;
    }

    public function recordSlug(string|Closure|null $slug): static
    {
        $this->recordSlug = $slug;

        return $this;
    }

    public function serverSideGeneration(bool|Closure $condition = true): static
    {
        $this->serverSideGeneration = $condition;

        return $this;
    }

    public function shouldUseServerSideGeneration(): bool
    {
        if ((bool) $this->evaluate($this->serverSideGeneration)) {
            return true;
        }

        if ($this->usesTranslatableTitle()) {
            return true;
        }

        return $this->usesSpatieIntegration();
    }

    public function getSeparator(): string
    {
        return (string) $this->evaluate($this->slugSeparator);
    }

    public function getMaxSlugLength(): ?int
    {
        $length = $this->evaluate($this->maxSlugLength);

        return is_numeric($length) ? (int) $length : null;
    }

    public function getSpatieSourceField(): ?string
    {
        $field = $this->evaluate($this->spatieSourceField);

        return filled($field) ? (string) $field : null;
    }

    public function getSpatieSlugField(): string
    {
        return (string) $this->evaluate($this->spatieSlugField);
    }

    public function getSpatieModelClass(): ?string
    {
        $modelClass = $this->evaluate($this->spatieModel);

        if (filled($modelClass)) {
            return (string) $modelClass;
        }

        $record = $this->resolveRecord();

        if ($record !== null) {
            return $record::class;
        }

        if ($this->isRootMounted()) {
            $formModel = $this->getModel();

            if (is_string($formModel) && $formModel !== '' && class_exists($formModel)) {
                return $formModel;
            }
        }

        return null;
    }

    public function usesSpatieIntegration(): bool
    {
        if (! SpatieSlugIntegration::isAvailable()) {
            return false;
        }

        $modelClass = $this->getSpatieModelClass();

        if (! filled($modelClass)) {
            return false;
        }

        return SpatieSlugIntegration::resolveSlugOptionsForModelClass(
            $modelClass,
            $this->getSpatieSlugField(),
        ) !== null;
    }

    public function getRecordSlug(): ?string
    {
        $slug = $this->evaluate($this->recordSlug);

        if (filled($slug)) {
            return $this->normalizeSlug((string) $slug);
        }

        $record = $this->resolveRecord();

        if ($record === null) {
            return null;
        }

        $attribute = $record->getAttribute($this->getSpatieSlugField());

        return is_string($attribute) && filled($attribute)
            ? $this->normalizeSlug($attribute)
            : null;
    }

    public function normalizeSlug(string $value): string
    {
        return SlugGenerator::normalize($value, $this->getSeparator(), $this->allowsHomepageSlug());
    }

    public function generateSlugFromSource(string $source, ?Get $get = null): string
    {
        if ($this->slugifyUsing instanceof Closure) {
            $slug = (string) $this->evaluate($this->slugifyUsing, ['source' => $source]);

            return $this->normalizeSlug($slug);
        }

        if (! $this->usesSpatieIntegration()) {
            return SlugGenerator::fromString(
                $source,
                $this->getSeparator(),
                $this->getMaxSlugLength(),
                $this->usesTranslatableTitle() ? $this->getSlugSourceLocale() : null,
            );
        }

        $modelClass = $this->getSpatieModelClass();
        $record = $this->resolveRecord();
        $model = $record ?? new $modelClass;
        $options = SpatieSlugIntegration::resolveSlugOptions($model, $this->getSpatieSlugField());

        if ($options === null && $record !== null && filled($modelClass) && $record::class !== $modelClass) {
            $options = SpatieSlugIntegration::resolveSlugOptions(new $modelClass, $this->getSpatieSlugField());
        }

        if ($options === null) {
            return SlugGenerator::fromString(
                $source,
                $this->getSeparator(),
                $this->getMaxSlugLength(),
                $this->usesTranslatableTitle() ? $this->getSlugSourceLocale() : null,
            );
        }

        $formAttributes = array_merge(
            $this->collectModelFormAttributes($get),
            SpatieSlugIntegration::collectFormAttributes(
                $model,
                $options,
                $source,
                $this->resolvePrimarySourceFieldName(),
                $this->getSpatieSourceField(),
                fn (string $field): ?string => $this->readSiblingFormValue($field, $get),
            ),
        );

        $locale = $this->usesTranslatableTitle()
            ? $this->getSlugSourceLocale()
            : null;

        return $this->normalizeSlug(SpatieSlugIntegration::generate(
            $source,
            $model,
            $this->getSpatieSlugField(),
            $this->getSpatieSourceField(),
            $formAttributes,
            $locale,
        ));
    }

    protected function resolvePrimarySourceFieldName(): ?string
    {
        $source = $this->evaluate($this->source);

        if (filled($source)) {
            $source = (string) $source;

            return str_contains($source, '.') ? Str::afterLast($source, '.') : $source;
        }

        return $this->getSpatieSourceField();
    }
}
