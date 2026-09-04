<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Pages\FlexFieldsPlaygroundCluster;
use Bjanczak\FilamentFlexFields\Filament\Pages\FlexFieldsPlaygroundComponentPage;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;

it('exposes one registry entry per playground component', function () {
    config()->set('filament-flex-fields.playground.enabled', true);

    expect(count(FlexFieldsPlaygroundRegistry::definitions()))->toBe(63)
        ->and(count(FlexFieldsPlaygroundRegistry::pageConfigurations()))->toBe(63);
});

it('orders playground definitions by category then sort', function () {
    $previousCategorySort = null;
    $previousSort = null;

    foreach (FlexFieldsPlaygroundRegistry::ordered() as $definition) {
        $categorySort = $definition['category']->sort();

        if ($previousCategorySort !== null) {
            expect($categorySort)->toBeGreaterThanOrEqual($previousCategorySort);
        }

        if ($previousCategorySort === $categorySort && $previousSort !== null) {
            expect($definition['sort'])->toBeGreaterThanOrEqual($previousSort);
        }

        $previousCategorySort = $categorySort;
        $previousSort = $definition['sort'];
    }

    expect(array_key_first(FlexFieldsPlaygroundRegistry::ordered()))->toBe('focus-outline');
});

it('does not expose playground page configurations when disabled', function () {
    config()->set('filament-flex-fields.playground.enabled', false);

    expect(FlexFieldsPlaygroundRegistry::pageConfigurations())->toBe([])
        ->and(FlexFieldsPlaygroundCluster::shouldRegisterNavigation())->toBeFalse();
});

it('registers cluster and component page classes when playground is enabled', function () {
    config()->set('filament-flex-fields.playground.enabled', true);

    expect(FlexFieldsPlaygroundCluster::shouldRegisterNavigation())->toBeTrue()
        ->and(FlexFieldsPlaygroundRegistry::firstSlug())->toBe('focus-outline')
        ->and(FlexFieldsPlaygroundRegistry::find('rating-column'))->not->toBeNull();
});

it('resolves component page definitions from configuration keys', function () {
    config()->set('filament-flex-fields.playground.enabled', true);

    $definition = FlexFieldsPlaygroundRegistry::find('phone-field');

    expect($definition)->not->toBeNull()
        ->and($definition['label'])->toBe('Phone field');
});

it('resolves playground slug from request path for authorization', function () {
    config()->set('filament-flex-fields.playground.enabled', true);

    $user = new User;
    $user->forceFill(['id' => 1]);
    auth()->login($user);

    app()->instance('request', Request::create('/admin/flex-fields-playground/user-column', 'GET'));

    expect(FlexFieldsPlaygroundComponentPage::canAccess())->toBeTrue();
});

it('renders playground stylesheet on component pages', function () {
    $stylesPartial = file_get_contents(__DIR__.'/../../resources/views/partials/playground-page-stylesheets.blade.php');

    expect($stylesPartial)->toContain('playgroundStylesheetHrefsForRequest()');
});

it('uses registry labels for sub-navigation entries', function () {
    $labels = array_column(FlexFieldsPlaygroundRegistry::ordered(), 'label');

    expect($labels)->toContain('RatingColumn', 'IconColumn', 'UserColumn', 'Phone field')
        ->and(count($labels))->toBe(63);
});

it('assigns a gravity icon to every playground sub-navigation entry', function () {
    foreach (FlexFieldsPlaygroundRegistry::ordered() as $slug => $definition) {
        expect($definition)->toHaveKey('icon')
            ->and($definition['icon'])->toStartWith('gravityui-');
    }

    expect(FlexFieldsPlaygroundRegistry::find('focus-outline')['icon'])->toBe(GravityIcon::Eye);
});

it('resolves every registered playground class from the container', function () {
    foreach (FlexFieldsPlaygroundRegistry::definitions() as $slug => $definition) {
        $playgroundClass = $definition['playground'];

        expect(class_exists($playgroundClass))
            ->toBeTrue("Playground class for [{$slug}] does not exist: {$playgroundClass}");

        expect(app($playgroundClass))->toBeObject();
    }
});

it('renders playground icons in cluster sub-navigation', function () {
    $cluster = file_get_contents(__DIR__.'/../../src/Filament/Pages/FlexFieldsPlaygroundCluster.php');

    expect($cluster)->toContain("->icon(\$definition['icon'])")
        ->and($cluster)->toContain("->group(\$definition['category']->label())");
});

it('assigns a playground category to every registry hub', function () {
    foreach (FlexFieldsPlaygroundRegistry::definitions() as $slug => $definition) {
        expect($definition)->toHaveKey('category')
            ->and($definition['category']->label())->toBeString()->not->toBeEmpty();
    }

    expect(FlexFieldsPlaygroundRegistry::groupedByCategory()['guides'] ?? null)->not->toBeEmpty()
        ->and(FlexFieldsPlaygroundRegistry::groupedByCategory()['pickers'] ?? null)->not->toBeEmpty()
        ->and(FlexFieldsPlaygroundRegistry::groupedByCategory()['date_and_time'] ?? null)->not->toBeEmpty();
});

it('maps every playground hub to an explicit design-system category', function () {
    $categories = [];

    foreach (FlexFieldsPlaygroundRegistry::definitions() as $slug => $definition) {
        $categories[$definition['category']->value][] = $slug;
    }

    expect($categories)->toHaveKeys([
        'guides',
        'navigation',
        'buttons',
        'pickers',
        'date_and_time',
        'colors',
        'controls',
        'collections',
        'text_input',
        'data_display',
        'media',
        'location',
    ])->and(count($categories, COUNT_RECURSIVE) - count($categories))->toBe(63);
});

it('expires NEW badges on v3 meta hubs', function () {
    $removedDumpSlugs = [
        'compliance-pack',
        'enterprise-control',
    ];

    foreach ($removedDumpSlugs as $slug) {
        expect(FlexFieldsPlaygroundRegistry::find($slug))->toBeNull("Docs-dump hub [{$slug}] must stay out of playground nav.");
    }

    foreach ([
        'schema-conditions',
        'field-intelligence',
        'composition-recipes',
        'hold-confirm',
        'admin-columns',
        'schedule-field',
        'barcode-scanner-field',
    ] as $slug) {
        $definition = FlexFieldsPlaygroundRegistry::find($slug);

        expect($definition)->not->toBeNull("Playground slug [{$slug}] must exist.")
            ->and($definition['badge'] ?? null)->toBeNull("V3 hub [{$slug}] should not ship a NEW badge in 3.1.");
    }

    expect(FlexFieldsPlaygroundRegistry::find('field-intelligence')['label'])->toBe('Calculated formulas')
        ->and(FlexFieldsPlaygroundRegistry::find('select-field')['badge'] ?? null)->toBeNull();
});

it('keeps component page class bound to the playground cluster', function () {
    expect(FlexFieldsPlaygroundComponentPage::getCluster())->toBe(FlexFieldsPlaygroundCluster::class);
});

it('builds command palette entries on the page class without Blade use statements', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/pages/flex-fields-playground-component.blade.php');

    expect($blade)
        ->toContain('commandPaletteEntries()')
        ->not->toContain('showreel')
        ->not->toContain('@php')
        ->not->toContain('use Bjanczak\\FilamentFlexFields\\');
});
