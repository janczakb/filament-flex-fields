<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait ResolvesSlugStatePaths
{
    protected function resolveSourcePath(string $source): string
    {
        if (str_starts_with($source, 'data.')) {
            return $source;
        }

        $path = $this->resolveOwnStatePath();
        $parent = str_contains($path, '.') ? Str::beforeLast($path, '.') : null;

        return filled($parent) ? "{$parent}.{$source}" : $source;
    }

    protected function resolveOwnStatePath(): string
    {
        if (! $this->isRootMounted()) {
            return $this->getName();
        }

        return $this->getStatePath();
    }

    protected function isRootMounted(): bool
    {
        try {
            $this->getContainer();

            return true;
        } catch (\Error) {
            return false;
        }
    }

    protected function resolveRecord(): ?Model
    {
        if (! $this->isRootMounted()) {
            return null;
        }

        $record = $this->getRecord();

        return $record instanceof Model ? $record : null;
    }

    public function getOperation(): string
    {
        if (! $this->isRootMounted()) {
            return 'create';
        }

        return $this->getContainer()->getOperation();
    }

    protected function readSiblingFormValue(string $fieldName, ?Get $get = null): ?string
    {
        if ($get !== null) {
            $value = $get($fieldName);

            if ($value === null && str_contains($fieldName, '.')) {
                $value = $get(Str::afterLast($fieldName, '.'));
            }

            if ($value !== null && is_scalar($value)) {
                return (string) $value;
            }
        }

        if (! $this->isRootMounted()) {
            return null;
        }

        try {
            $livewire = $this->getLivewire();
            $path = $this->resolveSiblingStatePath($fieldName);
            $value = data_get($livewire, $path);

            if ($value === null) {
                $value = data_get($livewire, "data.{$fieldName}");
            }

            if ($value === null) {
                return null;
            }

            return is_scalar($value) ? (string) $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, scalar|null>
     */
    protected function collectModelFormAttributes(?Get $get = null): array
    {
        $modelClass = $this->getSpatieModelClass();

        if (! filled($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        try {
            $model = $this->resolveRecord() ?? new $modelClass;
        } catch (\Throwable) {
            return [];
        }

        if (! $model instanceof Model) {
            return [];
        }

        $attributes = [];

        foreach ($this->resolveModelAttributeNames($model) as $field) {
            $value = $this->readFormAttributeValue($field, $get);

            if ($value !== null) {
                $attributes[$field] = $value;
            }
        }

        return $attributes;
    }

    /**
     * @return list<string>
     */
    protected function resolveModelAttributeNames(Model $model): array
    {
        $names = array_keys($model->getAttributes());

        $fillable = $model->getFillable();

        if ($fillable !== []) {
            $names = array_merge($names, $fillable);
        }

        if ($this->isRootMounted()) {
            try {
                $data = data_get($this->getLivewire(), 'data', []);

                if (is_array($data)) {
                    $names = array_merge($names, array_keys($data));
                }
            } catch (\Throwable) {
                // Ignore Livewire resolution failures during preview generation.
            }
        }

        $ignored = ['id', 'created_at', 'updated_at', 'deleted_at'];

        return array_values(array_unique(array_filter(
            $names,
            fn (mixed $name): bool => is_string($name)
                && $name !== ''
                && ! in_array($name, $ignored, true),
        )));
    }

    protected function readFormAttributeValue(string $fieldName, ?Get $get = null): mixed
    {
        if ($get !== null) {
            $value = $get($fieldName);

            if ($value === null && str_contains($fieldName, '.')) {
                $value = $get(Str::afterLast($fieldName, '.'));
            }

            if (is_scalar($value) || $value === null) {
                return $value;
            }
        }

        if (! $this->isRootMounted()) {
            return null;
        }

        try {
            $livewire = $this->getLivewire();
            $path = $this->resolveSiblingStatePath($fieldName);
            $value = data_get($livewire, $path);

            if ($value === null) {
                $value = data_get($livewire, "data.{$fieldName}");
            }

            if (is_scalar($value) || $value === null) {
                return $value;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    protected function resolveSiblingStatePath(string $fieldName): string
    {
        if (str_contains($fieldName, '.')) {
            return $fieldName;
        }

        $path = $this->resolveOwnStatePath();
        $parent = str_contains($path, '.') ? Str::beforeLast($path, '.') : null;

        return filled($parent) ? "{$parent}.{$fieldName}" : $fieldName;
    }
}
