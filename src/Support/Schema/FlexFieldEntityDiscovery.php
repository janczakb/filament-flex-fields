<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Concerns\HasFlexFields;
use Bjanczak\FilamentFlexFields\Data\FlexFieldEntity;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Throwable;

final class FlexFieldEntityDiscovery
{
    /**
     * @return list<FlexFieldEntity>
     */
    public function discover(): array
    {
        /** @var array<string, FlexFieldEntity> $entities */
        $entities = [];

        foreach ($this->discoverFromFilamentResources() as $entity) {
            $entities[$entity->key()] = $entity;
        }

        foreach ($this->discoverFromConfiguredPaths() as $entity) {
            $entities[$entity->key()] ??= $entity;
        }

        foreach ($this->discoverFromManualConfig() as $entity) {
            $entities[$entity->key()] = $entity;
        }

        return array_values(collect($entities)
            ->sortBy(fn (FlexFieldEntity $entity): int => $entity->sort)
            ->values()
            ->all());
    }

    /**
     * @return list<FlexFieldEntity>
     */
    private function discoverFromFilamentResources(): array
    {
        if (! FlexFieldsConfig::shouldDiscoverEntitiesFromFilamentResources()) {
            return [];
        }

        if (! class_exists(Filament::class)) {
            return [];
        }

        try {
            $panel = Filament::getCurrentPanel();
        } catch (Throwable) {
            return [];
        }

        if ($panel === null) {
            return [];
        }

        $entities = [];

        foreach ($panel->getResources() as $resourceClass) {
            if (! is_string($resourceClass) || ! is_subclass_of($resourceClass, Resource::class)) {
                continue;
            }

            $modelClass = $resourceClass::getModel();

            if (! is_string($modelClass) || ! $this->modelUsesFlexFields($modelClass)) {
                continue;
            }

            $label = $resourceClass::getPluralModelLabel();
            $icon = $resourceClass::getNavigationIcon();

            $entities[] = new FlexFieldEntity(
                modelClass: $modelClass,
                label: is_string($label) && filled($label) ? $label : class_basename($modelClass),
                icon: is_string($icon) ? $icon : null,
                resourceClass: $resourceClass,
                sort: (int) ($resourceClass::getNavigationSort() ?? 0),
            );
        }

        return $entities;
    }

    /**
     * @return list<FlexFieldEntity>
     */
    private function discoverFromConfiguredPaths(): array
    {
        $entities = [];

        foreach (FlexFieldsConfig::getEntityDiscoveryPaths() as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $finder = (new Finder)
                ->in($path)
                ->files()
                ->name('*.php');

            foreach ($finder as $file) {
                $class = $this->classFromPath($path, $file->getRealPath());

                if ($class === null || ! class_exists($class)) {
                    continue;
                }

                if (! $this->modelUsesFlexFields($class)) {
                    continue;
                }

                $entities[] = new FlexFieldEntity(
                    modelClass: $class,
                    label: Str::headline(class_basename($class)),
                );
            }
        }

        return $entities;
    }

    /**
     * @return list<FlexFieldEntity>
     */
    private function discoverFromManualConfig(): array
    {
        $entities = [];

        foreach (FlexFieldsConfig::getConfiguredEntities() as $modelClass => $config) {
            if (! is_string($modelClass)) {
                continue;
            }

            $label = is_array($config) ? ($config['label'] ?? class_basename($modelClass)) : class_basename($modelClass);
            $icon = is_array($config) ? ($config['icon'] ?? null) : null;
            $sort = is_array($config) ? (int) ($config['sort'] ?? 0) : 0;

            $entities[] = new FlexFieldEntity(
                modelClass: $modelClass,
                label: (string) $label,
                icon: is_string($icon) ? $icon : null,
                sort: $sort,
            );
        }

        return $entities;
    }

    /**
     * @param  class-string  $class
     */
    private function modelUsesFlexFields(string $class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        return in_array(HasFlexFields::class, class_uses_recursive($class), true);
    }

    private function classFromPath(string $basePath, string $filePath): ?string
    {
        $namespace = FlexFieldsConfig::getEntityDiscoveryNamespace();

        if (! is_string($namespace) || ! filled($namespace)) {
            return null;
        }

        $relative = Str::after($filePath, rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
        $relative = Str::replaceLast('.php', '', $relative);
        $relativeClass = str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

        return $namespace.'\\'.$relativeClass;
    }
}
