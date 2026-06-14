<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Assets;

use Filament\Support\Assets\AlpineComponent;
use Throwable;

class FlexFieldsAlpineComponent extends AlpineComponent
{
    /**
     * @var array<string, string>
     */
    protected static array $versionCache = [];

    public function getVersion(): string
    {
        $path = $this->getPublicPath();

        if (isset(self::$versionCache[$path])) {
            return self::$versionCache[$path];
        }

        try {
            if (file_exists($path)) {
                return self::$versionCache[$path] = (string) filemtime($path);
            }
        } catch (Throwable) {
        }

        return self::$versionCache[$path] = parent::getVersion();
    }
}
