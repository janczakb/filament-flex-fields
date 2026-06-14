<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

trait InteractsWithSlugUnique
{
    protected bool|Closure $slugUnique = true;

    /**
     * @var array<string, mixed>
     */
    protected array $slugUniqueParameters = [];

    protected ?Closure $slugUniqueScope = null;

    protected string|Closure|null $slugUniqueModel = null;

    protected bool|Closure $liveUniqueValidation = true;

    public function slugUnique(bool|Closure $condition = true): static
    {
        $this->slugUnique = $condition;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function slugUniqueParameters(array $parameters): static
    {
        $this->slugUniqueParameters = $parameters;

        return $this;
    }

    /**
     * Limit uniqueness to rows matching the given query scope.
     *
     * Use when multiple records share one table but must have unique slugs only
     * within a subset (tenant, type, locale, etc.). Different Eloquent models
     * with different tables (e.g. Post vs Event) are already isolated by default.
     */
    public function slugUniqueScope(?Closure $scope): static
    {
        $this->slugUniqueScope = $scope;

        return $this;
    }

    /**
     * Explicit model class for scoped uniqueness checks.
     *
     * Defaults to the form's Eloquent model when omitted.
     */
    public function slugUniqueModel(string|Closure|null $model): static
    {
        $this->slugUniqueModel = $model;

        return $this;
    }

    public function liveUniqueValidation(bool|Closure $condition = true): static
    {
        $this->liveUniqueValidation = $condition;

        return $this;
    }

    public function shouldEnforceSlugUnique(): bool
    {
        return (bool) $this->evaluate($this->slugUnique);
    }

    public function shouldValidateSlugUniquenessLive(): bool
    {
        return $this->shouldEnforceSlugUnique()
            && (bool) $this->evaluate($this->liveUniqueValidation);
    }

    public function getSlugUniqueValidationMessage(): string
    {
        return __('filament-flex-fields::default.validation.slug.unique');
    }

    public function isSlugValueUnique(?string $slug): bool
    {
        if (! $this->shouldEnforceSlugUnique()) {
            return true;
        }

        if (! is_string($slug) || trim($slug) === '') {
            return true;
        }

        if (method_exists($this, 'normalizeSlug')) {
            $slug = $this->normalizeSlug($slug);
        }

        if ($slug === '') {
            return true;
        }

        $parameters = $this->getSlugUniqueParameters();

        $column = (string) ($parameters['column']
            ?? (method_exists($this, 'getSpatieSlugField') ? $this->getSpatieSlugField() : null)
            ?? $this->getName());

        $modelClass = $this->resolveSlugUniqueModelClass($parameters);
        $table = $parameters['table'] ?? null;

        if (is_string($modelClass) && $modelClass !== '' && class_exists($modelClass)) {
            /** @var Builder $query */
            $query = $modelClass::query()->where($column, $slug);
        } elseif (filled($table)) {
            $query = DB::table($table)->where($column, $slug);
        } else {
            return true;
        }

        if (($parameters['ignoreRecord'] ?? true) === true) {
            $ignorable = $parameters['ignorable'] ?? function (): mixed {
                $record = $this->getRecord();

                return $record instanceof Model ? $record : null;
            };

            if ($ignorable instanceof Closure) {
                $ignorable = $this->evaluate($ignorable);
            }

            if ($ignorable instanceof Model) {
                if ($query instanceof Builder) {
                    $query->whereKeyNot($ignorable);
                } else {
                    $query->where($ignorable->getQualifiedKeyName(), '!=', $ignorable->getKey());
                }
            }
        }

        $modifyQueryUsing = $parameters['modifyQueryUsing']
            ?? $parameters['scope']
            ?? $this->slugUniqueScope;

        if ($modifyQueryUsing instanceof Closure) {
            $evaluated = $this->evaluate($modifyQueryUsing, [
                'query' => $query,
            ]);

            if ($evaluated instanceof Builder || $evaluated instanceof QueryBuilder) {
                $query = $evaluated;
            }
        }

        return ! $query->exists();
    }

    /**
     * @return array{available: bool, message: string|null}
     */
    public function checkSlugUniqueness(string $slug): array
    {
        $available = $this->isSlugValueUnique($slug);

        return [
            'available' => $available,
            'message' => $available ? null : $this->getSlugUniqueValidationMessage(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSlugUniqueParameters(): array
    {
        return $this->slugUniqueParameters;
    }

    public function getSlugUniqueScope(): ?Closure
    {
        return $this->slugUniqueScope;
    }

    public function getSlugUniqueModel(): ?string
    {
        $model = $this->evaluate($this->slugUniqueModel);

        return is_string($model) && $model !== '' ? $model : null;
    }

    protected function applySlugUniqueValidation(): void
    {
        if (! $this->shouldEnforceSlugUnique()) {
            return;
        }

        $this->validationMessages([
            'unique' => $this->getSlugUniqueValidationMessage(),
        ]);

        $parameters = $this->getSlugUniqueParameters();

        $modelClass = $this->resolveSlugUniqueModelClass($parameters);

        $column = $parameters['column']
            ?? (method_exists($this, 'getSpatieSlugField') ? $this->getSpatieSlugField() : null)
            ?? $this->getName();

        $ignorable = $parameters['ignorable'] ?? function (): mixed {
            $record = $this->getRecord();

            return $record instanceof Model ? $record : null;
        };

        $modifyQueryUsing = $parameters['modifyQueryUsing']
            ?? $parameters['scope']
            ?? $this->slugUniqueScope;

        if ($modifyQueryUsing instanceof Closure || filled($modelClass) || $this->shouldDeferSlugUniqueModelResolution($parameters)) {
            $this->scopedUnique(
                model: $parameters['model']
                    ?? $this->slugUniqueModel
                    ?? (filled($modelClass)
                        ? $modelClass
                        : fn (): ?string => $this->resolveSlugUniqueModelClass($parameters)),
                column: $column,
                ignoreRecord: $parameters['ignoreRecord'] ?? true,
                ignorable: $ignorable,
                modifyQueryUsing: $modifyQueryUsing,
            );

            return;
        }

        $table = $parameters['table'] ?? null;

        if ($table === null && filled($modelClass)) {
            $table = (new $modelClass)->getTable();
        }

        $this->unique(
            table: $table,
            column: $column,
            ignoreRecord: $parameters['ignoreRecord'] ?? true,
            ignorable: $ignorable,
            modifyRuleUsing: $parameters['modifyRuleUsing'] ?? null,
        );
    }

    protected function shouldDeferSlugUniqueModelResolution(array $parameters): bool
    {
        if (filled($parameters['model'] ?? null) || $this->slugUniqueModel !== null) {
            return false;
        }

        try {
            $this->getContainer();
        } catch (\Error) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function resolveSlugUniqueModelClass(array $parameters): ?string
    {
        $model = $parameters['model']
            ?? $this->getSlugUniqueModel()
            ?? (method_exists($this, 'getSpatieModelClass') ? $this->getSpatieModelClass() : null);

        if (is_string($model) && $model !== '' && class_exists($model)) {
            return $model;
        }

        try {
            $this->getContainer();
        } catch (\Error) {
            return null;
        }

        $record = $this->getRecord();

        if ($record instanceof Model) {
            return $record::class;
        }

        $formModel = $this->getModel();

        if (is_string($formModel) && $formModel !== '' && class_exists($formModel)) {
            return $formModel;
        }

        return null;
    }
}
