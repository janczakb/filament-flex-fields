<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableFieldFactory;

it('localeFieldUsing can replace the cloned field name', function (): void {
    $template = FlexTextInput::make('title')->label('Title');
    $tab = TranslatableTab::make('PL')->locale('pl');

    $field = TranslatableFieldFactory::make(
        template: $template,
        locale: 'pl',
        tab: $tab,
        localeFieldUsing: fn (): FlexTextInput => FlexTextInput::make('custom_pl'),
    );

    expect($field->getName())->toBe('custom_pl');
});

it('preserves flex text input rounding on locale clones', function (): void {
    $template = FlexTextInput::make('title')->rounding('full');
    $tab = TranslatableTab::make('EN')->locale('en');

    $field = TranslatableFieldFactory::make(
        template: $template,
        locale: 'en',
        tab: $tab,
    );

    expect($field)->toBeInstanceOf(FlexTextInput::class)
        ->and($field->getRounding())->toBe('full')
        ->and($field->getWrapperClasses())->toContain('fff-rounding-full')
        ->and($field->shouldShowFocusOutline())->toBeTrue();
});
