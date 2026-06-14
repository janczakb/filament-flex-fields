<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableLocales;
use Filament\Schemas\Components\Section;

it('builds locale tabs for a single field schema', function (): void {
    $component = TranslatableFields::make('Content')
        ->locales(['ar' => 'Arabic', 'en' => 'English'])
        ->schema([
            FlexTextInput::make('title'),
        ]);

    $tabs = $component->buildTranslatableTabs();

    expect($tabs)->toHaveCount(2)
        ->and($tabs[0])->toBeInstanceOf(TranslatableTab::class)
        ->and($tabs[0]->getLocale())->toBe('ar')
        ->and($tabs[1]->getLocale())->toBe('en');
});

it('resolves locales from translatable config', function (): void {
    config()->set('filament-flex-fields.translatable.locales', ['pl', 'en']);

    expect(TranslatableLocales::resolve(null))->toBe([
        'pl' => 'PL',
        'en' => 'EN',
    ]);
});

it('resolves locale labels separately from locale codes', function (): void {
    config()->set('filament-flex-fields.translatable.locales', ['ar', 'en']);
    config()->set('filament-flex-fields.translatable.locale_labels', [
        'ar' => 'Arabic',
        'en' => 'English',
    ]);

    expect(TranslatableLocales::resolve(null))->toBe([
        'ar' => 'Arabic',
        'en' => 'English',
    ]);

    $component = TranslatableFields::make('Content')
        ->locales(['ar', 'en'])
        ->localesLabels([
            'ar' => 'Arabic',
            'en' => 'English',
        ])
        ->schema([FlexTextInput::make('title')]);

    expect($component->getLocales())->toBe([
        'ar' => 'Arabic',
        'en' => 'English',
    ]);
});

it('applies global configureUsing defaults from filament', function (): void {
    TranslatableFields::configureUsing(
        fn (TranslatableFields $component) => $component->separators(true),
        during: function (): void {
            $component = TranslatableFields::make('Content')
                ->locales(['en' => 'English'])
                ->schema([FlexTextInput::make('title')]);

            expect($component->hasSeparators())->toBeTrue();
        },
    );
});

it('exposes preset helpers for empty badges and direction', function (): void {
    $component = TranslatableFields::make('Article')
        ->locales(['ar' => 'Arabic', 'en' => 'English'])
        ->directionByLocale()
        ->emptyBadgeWhenAllFieldsAreEmpty('empty')
        ->activeTabWithValue()
        ->schema([
            FlexTextInput::make('title'),
            FlexTextareaField::make('body'),
        ]);

    expect($component->getLocales())->toBe([
        'ar' => 'Arabic',
        'en' => 'English',
    ]);
});

it('supports segment tab badges via fluent api', function (): void {
    $tab = SegmentTab::make('English')
        ->badge('empty')
        ->badgeColor('warning');

    expect($tab->getBadge())->toBe('empty')
        ->and($tab->getBadgeColor('empty'))->toBe('warning');
});

it('can opt into bordered tab panels', function (): void {
    $component = TranslatableFields::make('Content')
        ->locales(['en' => 'English'])
        ->borderedPanels()
        ->schema([FlexTextInput::make('title')]);

    expect($component->getExtraAttributes()['class'])->toContain('fff-translatable-fields--bordered');
});

it('registers translatable fields playground variants after slug field', function (): void {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $components = $builder->build();
    $state = $builder->defaultState();

    $sectionHeadings = collect($components)
        ->filter(fn ($component): bool => $component instanceof Section)
        ->map(fn (Section $section): string => (string) $section->getHeading())
        ->values()
        ->all();

    $slugIndex = array_search('Slug field', $sectionHeadings, true);
    $translatableIndex = array_search('Translatable Fields', $sectionHeadings, true);

    expect($slugIndex)->not->toBeFalse()
        ->and($translatableIndex)->not->toBeFalse()
        ->and($translatableIndex)->toBeGreaterThan($slugIndex)
        ->and($state)->toHaveKeys([
            'translatable_pg__headline',
            'translatable_pg__pair_title',
            'translatable_pg__pair_excerpt',
            'translatable_pg__block_title',
            'translatable_pg__block_summary',
            'translatable_pg__block_body',
            'translatable_pg__bordered_title',
            'translatable_pg__triple_tagline',
            'translatable_pg__rtl_title',
        ]);
});
