<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableAttributePath;

it('resolves locale state paths from relative template state paths', function (): void {
    $template = FlexTextInput::make('title')->statePath('metadata.title');

    expect(TranslatableAttributePath::relativeBasePath($template))->toBe('metadata.title')
        ->and(TranslatableAttributePath::localeStatePath($template, 'en'))->toBe('metadata.title.en')
        ->and(TranslatableAttributePath::storageAttribute($template))->toBe('title');
});

it('supports custom storage attribute resolution', function (): void {
    $component = TranslatableFields::make('Content')
        ->locales(['en' => 'EN'])
        ->storageAttributeUsing(fn (): string => 'custom_title')
        ->schema([FlexTextInput::make('title')]);

    expect($component->buildTranslatableTabs())->toHaveCount(1);
});

it('supports localeFieldUsing to replace default cloning', function (): void {
    $component = TranslatableFields::make('Content')
        ->locales(['pl' => 'PL'])
        ->localeFieldUsing(function (FlexTextInput $template, string $locale): FlexTextInput {
            return FlexTextInput::make("custom_{$locale}")
                ->label($template->getLabel());
        })
        ->schema([FlexTextInput::make('title')->label('Title')]);

    $tabs = $component->buildTranslatableTabs();

    expect($tabs)->toHaveCount(1)
        ->and($tabs[0]->getLocale())->toBe('pl');
});

it('exposes withRecommendedDefaults preset bundle', function (): void {
    $component = TranslatableFields::make('Article')
        ->locales(['en' => 'English'])
        ->withRecommendedDefaults('missing')
        ->schema([FlexTextInput::make('title')]);

    expect($component)->toBeInstanceOf(TranslatableFields::class);
});
