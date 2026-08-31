<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Media\BarcodeValue;

it('creates barcode value from array and serializes back', function () {
    $value = BarcodeValue::fromArray([
        'value' => '5901234123457',
        'format' => 'ean_13',
    ]);

    expect($value)->toBeInstanceOf(BarcodeValue::class)
        ->and($value?->value)->toBe('5901234123457')
        ->and($value?->format)->toBe('ean_13')
        ->and($value?->toArray())->toBe([
            'value' => '5901234123457',
            'format' => 'ean_13',
        ]);
});

it('normalizes empty barcode arrays to null', function () {
    expect(BarcodeValue::fromArray(['value' => '']))->toBeNull()
        ->and(BarcodeValue::fromArray(['value' => '   ']))->toBeNull()
        ->and(BarcodeValue::fromArray([]))->toBeNull();
});

it('accepts value-only payloads without format', function () {
    $value = BarcodeValue::fromArray(['value' => 'ABC-123']);

    expect($value?->value)->toBe('ABC-123')
        ->and($value?->format)->toBeNull()
        ->and($value?->toArray())->toBe([
            'value' => 'ABC-123',
            'format' => null,
        ]);
});

it('trims whitespace from barcode values', function () {
    $value = BarcodeValue::fromArray([
        'value' => '  QR-VALUE  ',
        'format' => ' qr ',
    ]);

    expect($value?->value)->toBe('QR-VALUE')
        ->and($value?->format)->toBe('qr');
});
