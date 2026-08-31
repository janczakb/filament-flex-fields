<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupResourceRegistrar;

it('keeps schema resource disabled by default', function () {
    config(['filament-flex-fields.schema.resource_enabled' => false]);

    expect(FlexFieldGroupResourceRegistrar::isEnabled())->toBeFalse();
});

it('reads schema resource flag from config when enabled', function () {
    config(['filament-flex-fields.schema.resource_enabled' => true]);

    expect(FlexFieldGroupResourceRegistrar::isEnabled())->toBeTrue();
});
