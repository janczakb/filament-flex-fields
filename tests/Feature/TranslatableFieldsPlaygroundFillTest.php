<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableHydrator;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Livewire\Livewire;
use Symfony\Component\ErrorHandler\Error\FatalError;

it('fills nested translatable array into locale field state immediately', function (): void {
    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Title')
            ->locales(['pl' => 'PL', 'en' => 'EN'])
            ->schema([
                FlexTextInput::make('slug__i18n_title')->hiddenLabel(),
            ]),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->fillForm([
            'slug__i18n_title' => ['pl' => 'Polski', 'en' => 'English'],
        ])
        ->assertSet('data.slug__i18n_title.pl', 'Polski')
        ->assertSet('data.slug__i18n_title.en', 'English');
});

it('fills nested translatable array into title slug locale tabs immediately', function (): void {
    TestableTranslatableForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'slug__i18n_title',
            fieldSlug: 'slug__i18n_slug',
            translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
            slugSourceLocale: 'pl',
        ),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->fillForm([
            'slug__i18n_title' => ['pl' => 'Przewodnik', 'en' => 'Guide'],
            'slug__i18n_slug' => 'przewodnik',
        ])
        ->assertSet('data.slug__i18n_title.pl', 'Przewodnik')
        ->assertSet('data.slug__i18n_title.en', 'Guide');
});

it('auto syncs locale field state during fill', function (): void {
    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Title')
            ->locales(['pl' => 'PL'])
            ->schema([
                FlexTextInput::make('slug__i18n_title')->hiddenLabel(),
            ]),
    ];

    $test = Livewire::test(TestableTranslatableForm::class)
        ->call('fillPlaygroundState', [
            'slug__i18n_title' => ['pl' => 'Polski'],
        ]);

    $translatable = $test->instance()->getSchema('form')->getComponents()[0];
    $plField = $translatable->getChildSchema()->getComponents()[0]
        ->getChildSchema()->getComponents()[0];

    expect($plField->getState())->toBe('Polski');
});

it('resolves rendered state for translatable locale fields before view render', function (): void {
    TestableTranslatableForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'slug__i18n_title',
            fieldSlug: 'slug__i18n_slug',
            translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
            slugSourceLocale: 'pl',
        ),
    ];

    $test = Livewire::test(TestableTranslatableForm::class)
        ->call('fillPlaygroundState', [
            'slug__i18n_title' => ['pl' => 'Przewodnik', 'en' => 'Guide'],
            'slug__i18n_slug' => 'przewodnik',
        ]);

    $fusedGroup = $test->instance()->getSchema('form')->getComponents()[0];
    $translatable = $fusedGroup->getChildSchema()->getComponents()[0];
    $plField = $translatable->getChildSchema()->getComponents()[0]
        ->getChildSchema()->getComponents()[0];

    expect($plField->getState())->toBe('Przewodnik')
        ->and(TranslatableHydrator::resolveRenderedState($plField))
        ->toBe('Przewodnik');
});

it('resolves absolute translatable slug source path when mounted', function (): void {
    TestableTranslatableForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'slug__i18n_title',
            fieldSlug: 'slug__i18n_slug',
            translatableLocales: ['pl' => 'PL'],
            slugSourceLocale: 'pl',
        ),
    ];

    $fusedGroup = Livewire::test(TestableTranslatableForm::class)
        ->instance()
        ->getSchema('form')
        ->getComponents()[0];

    $slugField = $fusedGroup->getChildSchema()->getComponents()[3];

    expect($slugField->getAlpineConfiguration()['sourcePath'])->toBe('data.slug__i18n_title.pl');
});

it('renders title input value attribute in html before alpine loads', function (): void {
    TestableTranslatableForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'slug__i18n_title',
            fieldSlug: 'slug__i18n_slug',
            translatableLocales: ['pl' => 'PL'],
            slugSourceLocale: 'pl',
        ),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)
        ->call('fillPlaygroundState', [
            'slug__i18n_title' => ['pl' => 'Przewodnik po Morzu Śródziemnym'],
            'slug__i18n_slug' => 'przewodnik',
        ])
        ->html(false);

    preg_match('/<input[^>]*fff-title-slug-field__title-input[^>]*>/s', $html, $inputMatch);

    expect($inputMatch[0] ?? '')
        ->toContain('fff-title-slug-field__title-input')
        ->toContain('value="Przewodnik po Morzu Śródziemnym"');
});

it('renders a single active locale panel in html for filled translatable state', function (): void {
    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Three-field content block')
            ->locales(['pl' => 'PL', 'en' => 'EN', 'de' => 'DE'])
            ->withRecommendedDefaults()
            ->schema([
                FlexTextInput::make('translatable_pg__block_title')->hiddenLabel(),
                FlexTextInput::make('translatable_pg__block_summary')->hiddenLabel(),
                FlexTextareaField::make('translatable_pg__block_body')->hiddenLabel()->rows(4),
            ]),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)
        ->call('fillPlaygroundState', [
            'translatable_pg__block_title' => [
                'pl' => 'Oferta premium',
                'en' => 'Premium offer',
                'de' => 'Premium-Angebot',
            ],
            'translatable_pg__block_summary' => [
                'pl' => 'Zajawka',
                'en' => 'Teaser',
                'de' => 'Teaser',
            ],
            'translatable_pg__block_body' => [
                'pl' => 'Opis',
                'en' => 'Body',
                'de' => 'Body',
            ],
        ])
        ->html(false);

    preg_match_all('/fff-segment-tabs__panel is-active/', $html, $activePanels);
    preg_match_all('/fff-segment-tabs__panel/', $html, $allPanels);

    expect($activePanels[0] ?? [])->toHaveCount(1)
        ->and($allPanels[0] ?? [])->toHaveCount(3)
        ->and($html)->toContain('Oferta premium')
        ->and($html)->not->toMatch('/fff-segment-item__badge[^>]*>[\s\n]*empty/i');
});

it('does not recurse when resolving child schema repeatedly', function (): void {
    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Title')
            ->locales(['pl' => 'PL'])
            ->schema([
                FlexTextInput::make('slug__i18n_title')->hiddenLabel(),
            ]),
    ];

    $translatable = Livewire::test(TestableTranslatableForm::class)
        ->instance()
        ->getSchema('form')
        ->getComponents()[0];

    expect(fn () => $translatable->getChildSchema())
        ->not->toThrow(FatalError::class);

    $translatable->getChildSchema();
    $translatable->getChildSchema();

    expect(true)->toBeTrue();
});
