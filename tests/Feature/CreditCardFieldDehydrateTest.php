<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField;

it('does not persist cvv when dehydrating credit card field state for storage', function () {
    $field = CreditCardField::make('payment');

    $dehydrated = $field->dehydrateStateForStorage([
        'number' => '4242424242424242',
        'name' => 'Jane Doe',
        'expiry' => '12/30',
        'cvv' => '456',
    ]);

    expect($dehydrated)->toBe([
        'number' => '4242424242424242',
        'name' => 'Jane Doe',
        'expiry' => '12/30',
    ])->and(array_keys($dehydrated))->not->toContain('cvv');
});
