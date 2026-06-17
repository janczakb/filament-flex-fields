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

    expect(count(FlexFieldsPlaygroundRegistry::definitions()))->toBe(49)
        ->and(count(FlexFieldsPlaygroundRegistry::pageConfigurations()))->toBe(49);
});

it('orders playground definitions by sort', function () {
    $sorts = array_column(FlexFieldsPlaygroundRegistry::ordered(), 'sort');
    $sorted = $sorts;
    sort($sorted);

    expect($sorts)->toBe($sorted)
        ->and(array_key_first(FlexFieldsPlaygroundRegistry::ordered()))->toBe('focus-outline');
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

    expect($stylesPartial)->toContain('playgroundStylesheetHrefForRequest()');
});

it('uses registry labels for sub-navigation entries', function () {
    $labels = array_column(FlexFieldsPlaygroundRegistry::ordered(), 'label');

    expect($labels)->toContain('RatingColumn', 'UserColumn', 'Phone field')
        ->and(count($labels))->toBe(49);
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

    expect($cluster)->toContain("->icon(\$definition['icon'])");
});

it('keeps component page class bound to the playground cluster', function () {
    expect(FlexFieldsPlaygroundComponentPage::getCluster())->toBe(FlexFieldsPlaygroundCluster::class);
});
