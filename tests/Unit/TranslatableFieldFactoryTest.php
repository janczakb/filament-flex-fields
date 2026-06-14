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
