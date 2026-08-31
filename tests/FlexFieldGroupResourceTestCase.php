<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests;

use Bjanczak\BladeGravityIcons\BladeGravityIconsServiceProvider;
use Bjanczak\FilamentFlexFields\FilamentFlexFieldsServiceProvider;
use Bjanczak\FilamentFlexFields\Tests\Support\FlexFieldsTestPanelProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Translation\TranslationServiceProvider;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Sluggable\SluggableServiceProvider;

abstract class FlexFieldGroupResourceTestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');

        $dataStore = app(DataStore::class);
        app()->instance(DataStore::class, $dataStore);

        view()->share('errors', new ViewErrorBag);

        Gate::define('manageFlexFieldSchemas', fn (?object $user = null): bool => $user !== null);
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
            NotificationsServiceProvider::class,
            SluggableServiceProvider::class,
            FlexFieldsTestPanelProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
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
        $app['config']->set('filament-flex-fields.schema.resource_enabled', true);
        $app['config']->set('filament-flex-fields.schema.management_page_enabled', true);
        $app['config']->set('filament-flex-fields.playground.enabled', false);
        $app['config']->set('filament-flex-fields.slug.url_host', null);
        $app['config']->set('filament-flex-fields.values_column', 'flex_field_values');
    }
}
