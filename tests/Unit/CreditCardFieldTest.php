<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField;

it('exposes credit card styling and configuration api', function () {
    $field = CreditCardField::make('payment')
        ->size('lg')
        ->variant('ocean')
        ->flipOnCvvFocus(false)
        ->numberLabel('Card number')
        ->nameLabel('Name on card')
        ->expiryLabel('Expires')
        ->cvvLabel('Security code')
        ->mark('Acme Inc.');

    expect($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('ocean')
        ->and($field->shouldFlipOnCvvFocus())->toBeFalse()
        ->and($field->getNumberLabel())->toBe('Card number')
        ->and($field->getNameLabel())->toBe('Name on card')
        ->and($field->getExpiryLabel())->toBe('Expires')
        ->and($field->getCvvLabel())->toBe('Security code')
        ->and($field->getMark())->toBe('Acme Inc.');
});

it('normalizes credit card state', function () {
    $field = CreditCardField::make('payment');

    expect($field->normalizeState([
        'number' => '4242 4242 4242 4242',
        'name' => ' Jan ',
        'expiry' => '1228',
        'cvv' => '12a3',
    ]))->toBe([
        'number' => '4242424242424242',
        'name' => 'Jan',
        'expiry' => '12/28',
        'cvv' => '123',
    ]);
});

it('strips cvv from dehydrated state', function () {
    $field = CreditCardField::make('payment');

    expect($field->dehydrateStateForStorage([
        'number' => '4242 4242 4242 4242',
        'name' => 'Jan Kowalski',
        'expiry' => '12/28',
        'cvv' => '123',
    ]))->toBe([
        'number' => '4242424242424242',
        'name' => 'Jan Kowalski',
        'expiry' => '12/28',
    ])->and($field->dehydrateStateForStorage([
        'number' => '4242424242424242',
        'name' => 'Jan',
        'expiry' => '12/28',
        'cvv' => '999',
    ]))->not->toHaveKey('cvv');
});

it('validates card numbers with luhn algorithm', function () {
    $field = CreditCardField::make('payment');

    expect($field->passesLuhnCheck('4242424242424242'))->toBeTrue()
        ->and($field->passesLuhnCheck('4000000000000002'))->toBeTrue()
        ->and($field->passesLuhnCheck('4242424242424241'))->toBeFalse()
        ->and($field->passesLuhnCheck('1234567890123456'))->toBeFalse();
});

it('rejects invalid luhn card numbers during validation', function () {
    $field = CreditCardField::make('payment');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('payment', [
        'number' => '4242424242424241',
        'name' => 'Jan',
        'expiry' => '12/28',
        'cvv' => '123',
    ], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.credit_card.invalid_number'));
});

it('allows valid luhn card numbers during validation', function () {
    $field = CreditCardField::make('payment');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('payment', [
        'number' => '4242424242424242',
        'name' => 'Jan',
        'expiry' => '12/28',
        'cvv' => '123',
    ], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBeNull();
});

it('rejects unsupported credit card variants', function () {
    CreditCardField::make('payment')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('includes wrapper classes for size card variant and input variant', function () {
    $field = CreditCardField::make('payment')
        ->size('sm')
        ->variant('slate')
        ->inputVariant('secondary');

    expect($field->getWrapperClasses())->toBe([
        'fff-credit-card-field',
        'fff-credit-card-field--sm',
        'fff-credit-card-field--slate',
        'fff-credit-card-field--input-secondary',
    ]);
});

it('enables focus outline by default', function () {
    expect(CreditCardField::make('payment')->shouldShowFocusOutline())->toBeTrue()
        ->and(CreditCardField::make('payment')->focusOutline(false)->shouldShowFocusOutline())->toBeFalse();
});

it('rejects unsupported credit card input variants', function () {
    CreditCardField::make('payment')->inputVariant('ghost')->getInputVariant();
})->throws(InvalidArgumentException::class);

it('uses translated default field labels', function () {
    $field = CreditCardField::make('payment');

    expect($field->getNumberLabel())->toBe(__('filament-flex-fields::default.credit_card.number'))
        ->and($field->getNameLabel())->toBe(__('filament-flex-fields::default.credit_card.name'))
        ->and($field->getExpiryLabel())->toBe(__('filament-flex-fields::default.credit_card.expiry'))
        ->and($field->getCvvLabel())->toBe(__('filament-flex-fields::default.credit_card.cvv'))
        ->and($field->getMark())->toBe(__('filament-flex-fields::default.credit_card.mark'));
});

it('does not use laravel required rule on the composite state', function () {
    $field = CreditCardField::make('payment')->required();

    expect($field->getRequiredValidationRule())->toBe('nullable')
        ->and($field->getValidationRules())->not->toContain('required');
});

it('requires each card subfield when the field is required', function () {
    $field = CreditCardField::make('payment')->required();

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $emptyState = [
        'number' => '',
        'name' => '',
        'expiry' => '',
        'cvv' => '',
    ];

    $message = null;
    $rule('payment', $emptyState, function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('validation.required', ['attribute' => 'Card number']));
});

it('allows empty card details when the field is optional', function () {
    $field = CreditCardField::make('payment');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('payment', [
        'number' => '',
        'name' => '',
        'expiry' => '',
        'cvv' => '',
    ], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBeNull();
});

it('validates card number length when partially filled', function () {
    $field = CreditCardField::make('payment');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('payment', [
        'number' => '4242',
        'name' => '',
        'expiry' => '',
        'cvv' => '',
    ], function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.credit_card.invalid_number'));
});

it('rejects invalid expiry months', function () {
    $field = CreditCardField::make('payment');

    expect($field->getExpiryValidationMessage('13/28'))
        ->toBe(__('filament-flex-fields::default.validation.credit_card.invalid_expiry'))
        ->and($field->getExpiryValidationMessage('00/28'))
        ->toBe(__('filament-flex-fields::default.validation.credit_card.invalid_expiry'))
        ->and($field->getExpiryValidationMessage('1/28'))
        ->toBe(__('filament-flex-fields::default.validation.credit_card.invalid_expiry'));
});

it('rejects expired cards', function () {
    $this->travelTo('2026-06-09');

    $field = CreditCardField::make('payment');

    expect($field->getExpiryValidationMessage('05/26'))
        ->toBe(__('filament-flex-fields::default.validation.credit_card.expired'))
        ->and($field->getExpiryValidationMessage('06/26'))
        ->toBeNull()
        ->and($field->getExpiryValidationMessage('07/28'))
        ->toBeNull();
});
