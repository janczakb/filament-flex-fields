<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Assets;

use Filament\Support\Assets\Css;
use Throwable;

class FlexFieldsCss extends Css
{
    /**
     * @var array<string, string>
     */
    protected static array $versionCache = [];

    public function getVersion(): string
    {
        $cacheKey = implode('|', array_filter([$this->getPublicPath(), $this->getPath()]));

        if (isset(self::$versionCache[$cacheKey])) {
            return self::$versionCache[$cacheKey];
        }

        try {
            foreach ([$this->getPublicPath(), $this->getPath()] as $path) {
                if (is_string($path) && file_exists($path)) {
                    return self::$versionCache[$cacheKey] = (string) filemtime($path);
                }
            }
        } catch (Throwable) {
        }

        return self::$versionCache[$cacheKey] = parent::getVersion();
    }
}
