<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NpsField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Illuminate\Validation\Rules\In;

it('exposes nps configuration via fluent api', function () {
    $field = NpsField::make('score')
        ->variant('segments')
        ->rounding('full')
        ->colorCoded(true)
        ->minLabel('Low')
        ->maxLabel('High')
        ->options([0 => 'Zero', 1 => 'One']);

    expect($field->getVariant())->toBe('segments')
        ->and($field->getRounding())->toBe('full')
        ->and($field->isColorCoded())->toBeTrue()
        ->and($field->getMinLabel())->toBe('Low')
        ->and($field->getMaxLabel())->toBe('High')
        ->and($field->getOptions())->toBe([0 => 'Zero', 1 => 'One']);
});

it('defaults to a zero through ten scale', function () {
    expect(NpsField::make('score')->getOptions())->toHaveCount(11)
        ->and(NpsField::make('score')->getOptions()[0])->toBe(0)
        ->and(NpsField::make('score')->getOptions()[10])->toBe(10);
});

it('maps color coded tones for detractors passives and promoters', function () {
    $field = NpsField::make('score')->colorCoded(true);

    expect($field->getOptionTone(0))->toBe('detractor')
        ->and($field->getOptionTone(6))->toBe('detractor')
        ->and($field->getOptionTone(7))->toBe('passive')
        ->and($field->getOptionTone(8))->toBe('passive')
        ->and($field->getOptionTone(9))->toBe('promoter')
        ->and($field->getOptionTone(10))->toBe('promoter')
        ->and($field->getOptionTone('a'))->toBeNull();
});

it('resolves emoji asset urls for the default five point scale', function () {
    $field = NpsField::make('mood')
        ->variant('emojis')
        ->options([
            0 => 'Awful',
            1 => 'Poor',
            2 => 'Neutral',
            3 => 'Good',
            4 => 'Excellent',
        ]);

    expect($field->getEmojiImage(0))->toContain('nps-field/emojis/0.webp')
        ->and($field->getEmojiImage(4))->toContain('nps-field/emojis/4.webp')
        ->and($field->getEmojiImage(9))->toBeNull();
});

it('does not emit inline style variables when color coded', function () {
    $field = NpsField::make('score')->colorCoded(true);

    expect($field->getOptionStyleVariables(0))->toBe([])
        ->and($field->getOptionStyleVariables(9))->toBe([]);
});

it('validates state against configured option keys', function () {
    $field = NpsField::make('score')
        ->options([1 => 'One', 2 => 'Two']);

    expect($field->getOptionKeys())->toBe([1, 2]);

    $rules = $field->getValidationRules();
    $inRule = collect($rules)->first(fn (mixed $rule): bool => $rule instanceof In);

    expect($rules)->toContain('nullable')
        ->and($inRule)->not->toBeNull()
        ->and((string) $inRule)->toBe('in:"1","2"');
});

it('supports custom icons for emoji variant options', function () {
    $field = NpsField::make('mood')
        ->variant('emojis')
        ->options([0 => 'Awful', 1 => 'Poor'])
        ->icons([0 => 'gravityui-circle-xmark', 1 => 'heroicon-o-hand-raised']);

    expect($field->getOptionIcon(0))->toBe('gravityui-circle-xmark')
        ->and($field->getOptionIcon(1))->toBe('heroicon-o-hand-raised')
        ->and($field->getOptionIcon(2))->toBeNull();
});

it('defaults to a null state', function () {
    expect(NpsField::make('score')->getDefaultState())->toBeNull();
});

it('supports required validation from filament field api', function () {
    expect(NpsField::make('score')->required()->getValidationRules())->toContain('required');
});

it('supports disabled options across variants', function () {
    $field = NpsField::make('score')
        ->options([0 => 'Zero', 1 => 'One', 2 => 'Two'])
        ->disabledOptions([1]);

    expect($field->isOptionDisabled(1))->toBeTrue()
        ->and($field->isOptionDisabled(2))->toBeFalse();
});

it('registers nps field assets with segment control dependency', function () {
    expect(FlexFieldAssets::stylesheetsFor('nps-field'))->toBe(['segment-control', 'nps-field']);
});
