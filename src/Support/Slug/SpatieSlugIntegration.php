<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Slug;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction;
use Spatie\Sluggable\Actions\GenerateSlugAction;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\Support\Config;
use Spatie\Sluggable\Support\SluggableAttributeResolver;
use Spatie\Sluggable\Support\TraitDetector;

class SpatieSlugIntegration
{
    public static function isAvailable(): bool
    {
        return class_exists('Spatie\\Sluggable\\SlugOptions')
            || class_exists('Spatie\\Sluggable\\Actions\\GenerateSlugAction')
            || trait_exists('Spatie\\Sluggable\\HasSlug');
    }

    /**
     * Generate a slug preview using Spatie's slug pipeline when available.
     *
     * @param  array<string, scalar|null>  $formAttributes  Live form values keyed by model attribute names.
     */
    public static function generate(
        string $source,
        Model $model,
        string $slugField = 'slug',
        ?string $sourceField = null,
        array $formAttributes = [],
        ?string $locale = null,
    ): string {
        if (! self::isAvailable()) {
            return SlugGenerator::fromString($source);
        }

        $options = self::resolveSlugOptions($model, $slugField);

        if ($options === null) {
            return SlugGenerator::fromString($source);
        }

        $options = self::alignSlugField($options, $slugField);

        self::hydrateModelFromForm($model, $source, $options, $sourceField, $formAttributes);

        if (self::usesTranslatableSlug($model)) {
            return self::generateTranslatableSlugPreview($model, $options, $locale);
        }

        return self::generateStandardSlugPreview($model, $options, $source);
    }

    /**
     * @return array<int, string>
     */
    public static function resolveSourceFieldNames(SlugOptions $options, ?string $fallbackSourceField = null): array
    {
        if ($options->generateSlugFrom instanceof Closure) {
            return filled($fallbackSourceField) ? [(string) $fallbackSourceField] : [];
        }

        if (is_array($options->generateSlugFrom)) {
            return $options->generateSlugFrom;
        }

        return filled($fallbackSourceField) ? [(string) $fallbackSourceField] : ['title'];
    }

    /**
     * @param  callable(string): (?string)  $readSiblingValue
     * @return array<string, scalar|null>
     */
    public static function collectFormAttributes(
        Model $model,
        SlugOptions $options,
        string $primarySource,
        ?string $primaryField,
        ?string $configuredSourceField,
        callable $readSiblingValue,
    ): array {
        $attributes = [];
        $resolvedPrimaryField = $configuredSourceField ?? $primaryField;

        if (filled($resolvedPrimaryField)) {
            $attributes[(string) $resolvedPrimaryField] = $primarySource;
        }

        foreach (self::resolveSourceFieldNames($options, $resolvedPrimaryField) as $field) {
            if (array_key_exists($field, $attributes)) {
                continue;
            }

            $value = $readSiblingValue($field);

            if ($value !== null) {
                $attributes[$field] = $value;
            }
        }

        return $attributes;
    }

    public static function resolveSlugOptions(Model $model, string $fallbackSlugField): ?SlugOptions
    {
        if (! self::isAvailable()) {
            return null;
        }

        if (method_exists($model, 'getSlugOptions')) {
            $options = $model->getSlugOptions();

            return $options instanceof SlugOptions ? $options : null;
        }

        if (class_exists(SluggableAttributeResolver::class)) {
            $options = SluggableAttributeResolver::resolveOptions($model::class);

            if ($options instanceof SlugOptions) {
                if (! filled($options->slugField ?? null)) {
                    $options->saveSlugsTo($fallbackSlugField);
                }

                return $options;
            }
        }

        return null;
    }

    public static function resolveSlugOptionsForModelClass(string $modelClass, string $fallbackSlugField = 'slug'): ?SlugOptions
    {
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        try {
            $model = new $modelClass;
        } catch (\Throwable) {
            return null;
        }

        if (! $model instanceof Model) {
            return null;
        }

        return self::resolveSlugOptions($model, $fallbackSlugField);
    }

    public static function usesSelfHealingUrls(Model $model, string $fallbackSlugField = 'slug'): bool
    {
        $options = self::resolveSlugOptions($model, $fallbackSlugField);

        return $options?->selfHealingUrls === true;
    }

    public static function getSelfHealingSeparator(Model $model, string $fallbackSlugField = 'slug'): string
    {
        $options = self::resolveSlugOptions($model, $fallbackSlugField);

        return $options?->selfHealingSeparator ?? SlugGenerator::DEFAULT_SEPARATOR;
    }

    public static function buildSelfHealingRouteKey(string $slug, Model $record, string $fallbackSlugField = 'slug'): string
    {
        $options = self::resolveSlugOptions($record, $fallbackSlugField);

        if ($options === null || ! $options->selfHealingUrls) {
            return $slug;
        }

        $identifier = $record->getKey();

        if ($identifier === null) {
            return $slug;
        }

        $action = Config::getAction(
            Config::ACTION_BUILD_SELF_HEALING_ROUTE_KEY,
            BuildSelfHealingRouteKeyAction::class,
        );

        return $action->execute($slug, $identifier, $options->selfHealingSeparator);
    }

    protected static function generateStandardSlugPreview(Model $model, SlugOptions $options, string $source): string
    {
        $action = self::resolveGenerateSlugAction();

        if (self::shouldSkipGeneration($action, $model, $options)) {
            $existing = $model->getAttribute($options->slugField);

            return is_string($existing) && $existing !== ''
                ? $existing
                : SlugGenerator::fromString($source, $options->slugSeparator, $options->maximumLength);
        }

        $action->generate($model, $options);

        $slug = $model->getAttribute($options->slugField);

        return is_string($slug) && $slug !== ''
            ? $slug
            : SlugGenerator::fromString($source, $options->slugSeparator, $options->maximumLength);
    }

    protected static function generateTranslatableSlugPreview(
        Model $model,
        SlugOptions $options,
        ?string $locale,
    ): string {
        $action = self::resolveGenerateSlugAction();
        $locale ??= method_exists($model, 'getLocale') ? $model->getLocale() : (string) config('app.locale');
        $slugField = $options->slugField;

        if (! method_exists($model, 'withLocale')) {
            return self::generateStandardSlugPreview($model, $options, '');
        }

        $slug = '';

        $model->withLocale($locale, function () use ($model, $options, $action, $slugField, $locale, &$slug): void {
            if ($options->preventOverwrite) {
                $existing = method_exists($model, 'getTranslation')
                    ? $model->getTranslation($slugField, $locale, false)
                    : null;

                if (filled($existing)) {
                    $slug = (string) $existing;

                    return;
                }
            }

            if ($action->shouldSkipBasedOnSkipWhen($options)) {
                $existing = method_exists($model, 'getTranslation')
                    ? $model->getTranslation($slugField, $locale, false)
                    : null;

                if (is_string($existing) && $existing !== '') {
                    $slug = $existing;
                }

                return;
            }

            $sourceString = $action->buildSourceString(
                $options,
                fn (string $fieldName): string => (string) data_get($model, $fieldName, ''),
                fn (Closure $source): string => (string) $source($model, $locale),
            );

            $candidate = $action->slugifySource($sourceString, $options);

            if ($options->generateUniqueSlugs) {
                $localeOptions = clone $options;
                $localeOptions->slugField = "{$slugField}->{$locale}";
                $candidate = $action->makeUnique($candidate, $model, $localeOptions);
            }

            $slug = $candidate;
        });

        return $slug !== ''
            ? $slug
            : SlugGenerator::fromString('', $options->slugSeparator, $options->maximumLength);
    }

    protected static function usesTranslatableSlug(Model $model): bool
    {
        return class_exists(HasTranslatableSlug::class)
            && TraitDetector::uses($model, HasTranslatableSlug::class);
    }

    protected static function alignSlugField(SlugOptions $options, string $slugField): SlugOptions
    {
        if ($options->slugField === $slugField) {
            return $options;
        }

        $aligned = clone $options;
        $aligned->slugField = $slugField;

        return $aligned;
    }

    /**
     * @param  array<string, scalar|null>  $formAttributes
     */
    protected static function hydrateModelFromForm(
        Model $model,
        string $source,
        SlugOptions $options,
        ?string $sourceField,
        array $formAttributes,
    ): void {
        foreach ($formAttributes as $attribute => $value) {
            if (is_scalar($value) || $value === null) {
                $model->setAttribute((string) $attribute, $value);
            }
        }

        if ($options->generateSlugFrom instanceof Closure) {
            if (filled($sourceField)) {
                $model->setAttribute($sourceField, $source);
            }

            return;
        }

        $fields = is_array($options->generateSlugFrom) && $options->generateSlugFrom !== []
            ? $options->generateSlugFrom
            : [filled($sourceField) ? $sourceField : 'title'];

        $primaryField = $sourceField ?? $fields[0];

        foreach ($fields as $field) {
            if ($field === $primaryField) {
                $model->setAttribute($field, $source);

                continue;
            }

            if (! $model->offsetExists($field) || blank($model->getAttribute($field))) {
                $model->setAttribute($field, $formAttributes[$field] ?? '');
            }
        }
    }

    protected static function resolveGenerateSlugAction(): GenerateSlugAction
    {
        if (class_exists(Config::class)) {
            return Config::getAction(Config::ACTION_GENERATE_SLUG, GenerateSlugAction::class);
        }

        return app(GenerateSlugAction::class);
    }

    protected static function shouldSkipGeneration(
        GenerateSlugAction $action,
        Model $model,
        SlugOptions $options,
    ): bool {
        if ($options->skipGenerateWhen instanceof Closure) {
            $shouldSkip = $options->skipGenerateWhen->call($model);

            if ($shouldSkip === true) {
                return true;
            }
        }

        if (! $options->preventOverwrite) {
            return false;
        }

        return $model->{$options->slugField} !== null;
    }
}
