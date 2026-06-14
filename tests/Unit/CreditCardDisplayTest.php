<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\CreditCardDisplay;

it('masks empty card number with bullets', function () {
    expect(CreditCardDisplay::maskedNumber(''))->toBe('•••• •••• •••• ••••');
});

it('masks partial card number while revealing typed digits', function () {
    expect(CreditCardDisplay::maskedNumber('4242'))->toBe('4242 •••• •••• ••••')
        ->and(CreditCardDisplay::maskedNumber('4242424242424242'))->toBe('4242 4242 4242 4242');
});

it('strips non-digits before masking', function () {
    expect(CreditCardDisplay::maskedNumber('42•• 42'))->toBe('4242 •••• •••• ••••');
});

it('masks empty cvv with three bullets', function () {
    expect(CreditCardDisplay::maskedCvv(''))->toBe('•••');
});

it('masks cvv digits with padding', function () {
    expect(CreditCardDisplay::maskedCvv('12'))->toBe('12•')
        ->and(CreditCardDisplay::maskedCvv('3123'))->toBe('3123');
});

it('sanitizes digit input', function () {
    expect(CreditCardDisplay::sanitizeDigits('42•• 42'))->toBe('4242')
        ->and(CreditCardDisplay::sanitizeDigits('????'))->toBe('');
});

it('formats card number input with spaced groups', function () {
    expect(CreditCardDisplay::formatNumberInput(''))->toBe('')
        ->and(CreditCardDisplay::formatNumberInput('4242'))->toBe('4242')
        ->and(CreditCardDisplay::formatNumberInput('4242424242424242'))->toBe('4242 4242 4242 4242')
        ->and(CreditCardDisplay::formatNumberInput('42•• 4242'))->toBe('4242 42');
});
