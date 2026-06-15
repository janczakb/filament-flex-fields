<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests;

use Bjanczak\BladeGravityIcons\BladeGravityIconsServiceProvider;
use Bjanczak\FilamentFlexFields\FilamentFlexFieldsServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Illuminate\Translation\TranslationServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Sluggable\SluggableServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
    }

    protected function getPackageProviders($app): array
    {
        return [
            TranslationServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeGravityIconsServiceProvider::class,
            FilamentFlexFieldsServiceProvider::class,
            FilamentServiceProvider::class,
            LivewireServiceProvider::class,
            FormsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            TablesServiceProvider::class,
            SluggableServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app->setLocale('en');

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.locale', 'en');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('filament-flex-fields.enabled', true);
        $app['config']->set('filament-flex-fields.slug.url_host', null);
        $app['config']->set('filament-flex-fields.values_column', 'flex_field_values');
        $app['config']->set('filament-flex-fields.ui.number_stepper_size', 'md');
        $app['config']->set('filament-flex-fields.ui.segment_size', 'md');
        $app['config']->set('filament-flex-fields.ui.slider_size', 'md');
        $app['config']->set('filament-flex-fields.ui.switch_size', 'md');
    }
}
