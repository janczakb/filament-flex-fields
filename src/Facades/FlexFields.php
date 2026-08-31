<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Facades;

use Bjanczak\FilamentFlexFields\Enums\Density;
use Bjanczak\FilamentFlexFields\Support\Theme\FlexFieldsTheme;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Density density()
 * @method static FlexFieldsTheme setDensity(\Bjanczak\FilamentFlexFields\Enums\Density|string $density)
 * @method static array<string, mixed> theme()
 * @method static FlexFieldsTheme setTheme(array<string, mixed> $theme)
 * @method static FlexFieldsTheme mergeTheme(array<string, mixed> $theme)
 * @method static array<string, string> toHtmlAttributes()
 * @method static array<string, string> toCssVariables()
 *
 * @see FlexFieldsTheme
 */
class FlexFields extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FlexFieldsTheme::class;
    }

    public static function density(): Density
    {
        return static::getFacadeRoot()->density();
    }

    /**
     * @return array<string, mixed>
     */
    public static function theme(): array
    {
        return static::getFacadeRoot()->theme();
    }
}
