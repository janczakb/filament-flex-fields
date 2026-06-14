<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields;

use Bjanczak\FilamentFlexFields\Filament\Pages\FlexFieldsPlaygroundCluster;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentFlexFieldsPlugin implements Plugin
{
    public const PACKAGE_NAME = 'janczakb/filament-flex-fields';

    public const PACKAGE_URL = 'https://github.com/janczakb/filament-flex-fields';

    protected ?string $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected ?string $navigationLabel = null;

    protected ?string $navigationIcon = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-flex-fields';
    }

    public function register(Panel $panel): void
    {
        if (! config('filament-flex-fields.enabled', true)) {
            return;
        }

        if (FlexFieldsPlaygroundRegistry::isEnabled()) {
            $panel->pages([
                FlexFieldsPlaygroundCluster::class,
                ...FlexFieldsPlaygroundRegistry::pageConfigurations(),
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup ?? config('filament-flex-fields.navigation.group');
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort ?? config('filament-flex-fields.navigation.sort');
    }

    public function navigationLabel(?string $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): ?string
    {
        return $this->navigationLabel ?? config('filament-flex-fields.navigation.label');
    }

    public function navigationIcon(?string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): ?string
    {
        return $this->navigationIcon ?? config('filament-flex-fields.navigation.icon');
    }
}
