<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Illuminate\Database\Eloquent\Model;

class SpatieTranslatableIntegration
{
    public static function isAvailable(): bool
    {
        return trait_exists('Spatie\\Translatable\\HasTranslations');
    }

    public static function modelUsesTranslatable(?Model $model, string $attribute): bool
    {
        if ($model === null || ! self::isAvailable()) {
            return false;
        }

        if (! method_exists($model, 'getTranslatableAttributes')) {
            return false;
        }

        return in_array($attribute, $model->getTranslatableAttributes(), true);
    }

    public static function assertCompatible(?string $modelClass, string $attribute, bool $spatieTranslatable): void
    {
        if (! $spatieTranslatable || blank($modelClass)) {
            return;
        }

        if (! self::isAvailable()) {
            return;
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            return;
        }

        /** @var Model $model */
        $model = new $modelClass;

        if (! self::modelUsesTranslatable($model, $attribute)) {
            return;
        }
    }

    /**
     * @param  class-string<Model>|null  $modelClass
     */
    public static function shouldUseSpatieHydration(?string $modelClass, string $attribute, bool $spatieTranslatable): bool
    {
        if (! $spatieTranslatable) {
            return false;
        }

        if (! self::isAvailable() || blank($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return $spatieTranslatable;
        }

        /** @var Model $model */
        $model = new $modelClass;

        return self::modelUsesTranslatable($model, $attribute) || $spatieTranslatable;
    }
}
