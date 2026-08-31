<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\TranslatableDirectionScope;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ImageChoiceCards;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableLocaleDirection;
use Filament\Forms\Components\TextInput;

it('detects rtl locales by exact code and primary language subtag', function (): void {
    expect(TranslatableLocaleDirection::isRtlLocale('ar'))->toBeTrue()
        ->and(TranslatableLocaleDirection::isRtlLocale('ar-SA'))->toBeTrue()
        ->and(TranslatableLocaleDirection::isRtlLocale('he_IL'))->toBeTrue()
        ->and(TranslatableLocaleDirection::isRtlLocale('en'))->toBeFalse()
        ->and(TranslatableLocaleDirection::isRtlLocale('en-US'))->toBeFalse();
});

it('resolves ltr and rtl directions from locale', function (): void {
    expect(TranslatableLocaleDirection::resolveDirection('ar'))->toBe('rtl')
        ->and(TranslatableLocaleDirection::resolveDirection('pl'))->toBe('ltr');
});

it('applies dir to input attributes for fields with extra input support', function (): void {
    $field = FlexTextInput::make('title');

    TranslatableLocaleDirection::applyToField($field, 'ar');

    expect($field->getExtraInputAttributes()['dir'] ?? null)->toBe('rtl')
        ->and($field->getExtraAttributes()['dir'] ?? null)->toBeNull();
});

it('applies ltr dir to input attributes for non-rtl locales', function (): void {
    $field = FlexTextInput::make('title');

    TranslatableLocaleDirection::applyToField($field, 'en');

    expect($field->getExtraInputAttributes()['dir'] ?? null)->toBe('ltr');
});

it('applies dir to textarea input attributes', function (): void {
    $field = FlexTextareaField::make('body');

    TranslatableLocaleDirection::applyToField($field, 'fa');

    expect($field->getExtraInputAttributes()['dir'] ?? null)->toBe('rtl');
});

it('falls back to field wrapper attributes when input direction is unsupported', function (): void {
    $field = ImageChoiceCards::make('body_type')->options(['slim' => 'Slim']);

    TranslatableLocaleDirection::applyToField($field, 'ar');

    expect($field->getExtraAttributes()['dir'] ?? null)->toBe('rtl')
        ->and(TranslatableLocaleDirection::supportsInputDirection($field))->toBeFalse();
});

it('supports forcing wrapper direction for backward compatibility', function (): void {
    $field = FlexTextInput::make('title');

    TranslatableLocaleDirection::applyToField($field, 'ar', TranslatableDirectionScope::Field);

    expect($field->getExtraAttributes()['dir'] ?? null)->toBe('rtl')
        ->and($field->getExtraInputAttributes()['dir'] ?? null)->toBeNull();
});

it('supports forcing input direction explicitly', function (): void {
    $field = TextInput::make('title');

    TranslatableLocaleDirection::applyToField($field, 'he', TranslatableDirectionScope::Input);

    expect($field->getExtraInputAttributes()['dir'] ?? null)->toBe('rtl');
});

it('merges direction attributes without overwriting unrelated extras', function (): void {
    $field = FlexTextInput::make('title')
        ->extraAttributes(['class' => 'custom-wrapper'])
        ->extraInputAttributes(['autocomplete' => 'off']);

    TranslatableLocaleDirection::applyToField($field, 'ar');

    expect($field->getExtraAttributes()['class'])->toBe('custom-wrapper')
        ->and($field->getExtraInputAttributes()['autocomplete'])->toBe('off')
        ->and($field->getExtraInputAttributes()['dir'])->toBe('rtl');
});

it('applies directionByLocale preset to cloned translatable fields via input attributes', function (): void {
    $component = TranslatableFields::make('Article')
        ->locales(['ar' => 'Arabic', 'en' => 'English'])
        ->directionByLocale()
        ->schema([
            FlexTextInput::make('title'),
        ]);

    $field = FlexTextInput::make('title');

    foreach ($component->getTranslatableFieldModifiers() as $modifier) {
        $field->evaluate($modifier, ['locale' => 'ar']);
    }

    expect($field->getExtraInputAttributes()['dir'] ?? null)->toBe('rtl')
        ->and($field->getExtraAttributes()['dir'] ?? null)->toBeNull();
});

it('applies directionByLocale field scope to the wrapper when requested', function (): void {
    $component = TranslatableFields::make('Article')
        ->locales(['ar' => 'Arabic'])
        ->directionByLocale(TranslatableDirectionScope::Field)
        ->schema([
            FlexTextInput::make('title'),
        ]);

    $field = FlexTextInput::make('title');

    foreach ($component->getTranslatableFieldModifiers() as $modifier) {
        $field->evaluate($modifier, ['locale' => 'ar']);
    }

    expect($field->getExtraAttributes()['dir'] ?? null)->toBe('rtl')
        ->and($field->getExtraInputAttributes()['dir'] ?? null)->toBeNull();
});
