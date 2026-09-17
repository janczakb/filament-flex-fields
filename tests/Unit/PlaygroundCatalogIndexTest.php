<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Pages\FlexFieldsPlaygroundIndexPage;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Bjanczak\FilamentFlexFields\Support\Playground\PlaygroundArtCatalog;
use Illuminate\Foundation\Auth\User;

it('maps known playground hubs to existing art webp files on github raw', function (): void {
    expect(PlaygroundArtCatalog::filenameFor('phone-field'))->toBe('phone-field.webp')
        ->and(PlaygroundArtCatalog::urlFor('phone-field'))
        ->toBe('https://raw.githubusercontent.com/janczakb/filament-flex-fields/main/art/phone-field.webp')
        ->and(PlaygroundArtCatalog::urlFor('focus-outline'))->toBeNull()
        ->and(PlaygroundArtCatalog::filenameFor('missing-hub'))->toBeNull();
});

it('only maps art files that exist in the package art directory', function (): void {
    foreach (PlaygroundArtCatalog::mappedFilenames() as $slug => $filename) {
        expect(PlaygroundArtCatalog::filenameFor($slug))->toBe($filename)
            ->and(is_file(PlaygroundArtCatalog::artDirectory().DIRECTORY_SEPARATOR.$filename))->toBeTrue();
    }
});

it('builds catalog sections covering every registered playground hub', function (): void {
    config()->set('filament-flex-fields.playground.enabled', true);

    $page = app(FlexFieldsPlaygroundIndexPage::class);
    $sections = $page->catalogSections();
    $slugs = collect($sections)->flatMap(fn (array $section) => collect($section['hubs'])->pluck('slug'))->all();

    expect($sections)->not->toBeEmpty()
        ->and($slugs)->toHaveCount(count(FlexFieldsPlaygroundRegistry::definitions()))
        ->and($slugs)->toContain('phone-field', 'color-swatch', 'map-picker');

    $phone = collect($sections)->flatMap(fn (array $section) => $section['hubs'])
        ->firstWhere('slug', 'phone-field');

    expect($phone['image'])->toContain('/art/phone-field.webp')
        ->and($phone['icon'])->toStartWith('gravityui-');
});

it('allows the playground index when playground is enabled', function (): void {
    config()->set('filament-flex-fields.playground.enabled', true);

    $user = new User;
    $user->forceFill(['id' => 1]);
    auth()->login($user);

    expect(FlexFieldsPlaygroundIndexPage::canAccess())->toBeTrue()
        ->and(FlexFieldsPlaygroundIndexPage::getSlug())->toBe('index');
});

it('puts all-components first in playground sub-navigation source', function (): void {
    $cluster = file_get_contents(__DIR__.'/../../src/Filament/Pages/FlexFieldsPlaygroundCluster.php');
    $plugin = file_get_contents(__DIR__.'/../../src/FilamentFlexFieldsPlugin.php');

    expect($cluster)
        ->toContain("NavigationItem::make('All components')")
        ->toContain('FlexFieldsPlaygroundIndexPage::getUrl()')
        ->and($plugin)->toContain('FlexFieldsPlaygroundIndexPage::class');
});
