<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableTitle;
use Bjanczak\FilamentFlexFields\Tests\Support\SlugFieldTranslatablePost;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableSlugForm;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    TestableSlugForm::$formSchema = [];
    TestableSlugForm::$modelClass = SlugFieldTranslatablePost::class;

    Schema::dropIfExists('slug_field_translatable_posts');

    Schema::create('slug_field_translatable_posts', function (Blueprint $table): void {
        $table->id();
        $table->json('title');
        $table->string('slug')->unique();
        $table->timestamps();
    });
});

it('auto-generates slug from the configured source locale title on create', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
            slugSourceLocale: 'pl',
        ),
    ];

    Livewire::test(TestableSlugForm::class)
        ->set('data.title.pl', 'Witaj Świecie')
        ->assertSet('data.slug', 'witaj-swiecie');
});

it('does not regenerate slug when a non-source locale title changes', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
            slugSourceLocale: 'pl',
        ),
    ];

    Livewire::test(TestableSlugForm::class)
        ->set('data.title.pl', 'Polski Tytuł')
        ->assertSet('data.slug', 'polski-tytul')
        ->set('data.title.en', 'English Title')
        ->assertSet('data.slug', 'polski-tytul');
});

it('preserves slug on edit for translatable titles', function (): void {
    $post = SlugFieldTranslatablePost::create([
        'title' => ['pl' => 'Stary tytuł', 'en' => 'Old title'],
        'slug' => 'stary-tytul',
    ]);

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
            slugSourceLocale: 'pl',
        ),
    ];

    Livewire::test(TestableSlugForm::class, ['record' => $post])
        ->set('data.title.pl', 'Nowy tytuł')
        ->assertSet('data.slug', 'stary-tytul');
});

it('hydrates json title state into locale tabs', function (): void {
    $post = SlugFieldTranslatablePost::create([
        'title' => ['pl' => 'Polski', 'en' => 'English'],
        'slug' => 'polski',
    ]);

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
            slugSourceLocale: 'pl',
        ),
    ];

    Livewire::test(TestableSlugForm::class, ['record' => $post])
        ->assertSet('data.title.pl', 'Polski')
        ->assertSet('data.title.en', 'English');
});

it('resolves translatable slug source path on slug field', function (): void {
    $field = SlugField::make('slug')
        ->translatableTitle()
        ->titleLocales(['pl' => 'PL', 'en' => 'EN'])
        ->slugSourceLocale('en')
        ->translatableTitleField('title');

    expect($field->usesTranslatableTitle())->toBeTrue()
        ->and($field->getSlugSourceLocale())->toBe('en')
        ->and($field->getSourceStatePath())->toBe('title.en');
});

it('resolves locales from config when translatable locales are omitted', function (): void {
    config()->set('filament-flex-fields.slug.translatable_locales', ['pl', 'en']);
    config()->set('filament-flex-fields.slug.slug_source_locale', 'pl');

    expect(TranslatableTitle::resolveLocales(null))->toBe([
        'pl' => 'PL',
        'en' => 'EN',
    ])->and(TranslatableTitle::resolveSlugSourceLocale(null, ['pl' => 'PL', 'en' => 'EN']))->toBe('pl');
});

it('normalizes json title state for translatable hydration', function (): void {
    expect(TranslatableTitle::normalizeHydratedState('{"pl":"A","en":"B"}'))->toBe([
        'pl' => 'A',
        'en' => 'B',
    ]);
});

it('requires only the slug source locale title by default', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
            slugSourceLocale: 'pl',
        ),
    ];

    $valid = Livewire::test(TestableSlugForm::class)
        ->set('data.title.pl', 'Polski tytuł')
        ->set('data.title.en', '');

    expect(fn () => $valid->instance()->getSchema('form')->validate())->not->toThrow(ValidationException::class);

    $invalid = Livewire::test(TestableSlugForm::class)
        ->set('data.title.pl', '')
        ->set('data.title.en', 'English title')
        ->set('data.slug', 'some-slug');

    expect(fn () => $invalid->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('requires all translatable title locales when requiredTitleLocales is all', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
            slugSourceLocale: 'pl',
            requiredTitleLocales: 'all',
        ),
    ];

    $invalid = Livewire::test(TestableSlugForm::class)
        ->set('data.title.pl', 'Polski tytuł')
        ->set('data.title.en', '')
        ->set('data.slug', 'polski-tytul');

    expect(fn () => $invalid->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('requires only configured locales when requiredTitleLocales is a list', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
            slugSourceLocale: 'pl',
            requiredTitleLocales: ['en'],
        ),
    ];

    $valid = Livewire::test(TestableSlugForm::class)
        ->set('data.title.pl', '')
        ->set('data.title.en', 'English title')
        ->set('data.slug', 'english-title');

    expect(fn () => $valid->instance()->getSchema('form')->validate())->not->toThrow(ValidationException::class);

    $invalid = Livewire::test(TestableSlugForm::class)
        ->set('data.title.pl', 'Polski tytuł')
        ->set('data.title.en', '')
        ->set('data.slug', 'polski-tytul');

    expect(fn () => $invalid->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('builds translatable title tabs with TranslatableFields', function (): void {
    $group = TitleSlugField::make(
        translatableLocales: ['pl' => 'PL', 'en' => 'EN', 'de' => 'DE'],
        slugSourceLocale: 'pl',
    );

    $titleComponent = $group->getDefaultChildComponents()[0];

    expect($titleComponent)->toBeInstanceOf(TranslatableFields::class)
        ->and($titleComponent->buildTranslatableTabs())->toHaveCount(3);
});

it('passes spatieTranslatable to TranslatableFields title tabs', function (): void {
    $group = TitleSlugField::make(
        translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
        slugSourceLocale: 'pl',
        spatieTranslatable: true,
    );

    $titleComponent = $group->getDefaultChildComponents()[0];

    expect($titleComponent)->toBeInstanceOf(TranslatableFields::class)
        ->and($titleComponent->shouldUseSpatieTranslatable())->toBeTrue();
});

it('supports translatableFieldsConfigurator passthrough', function (): void {
    $group = TitleSlugField::make(
        translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
        slugSourceLocale: 'pl',
        translatableFieldsConfigurator: fn (TranslatableFields $fields): TranslatableFields => $fields
            ->withRecommendedDefaults('missing'),
    );

    $titleComponent = $group->getDefaultChildComponents()[0];

    expect($titleComponent)->toBeInstanceOf(TranslatableFields::class);
});

it('enables empty tab badges on translatable title tabs by default', function (): void {
    $group = TitleSlugField::make(
        fieldTitle: 'slug__i18n_title',
        translatableLocales: ['pl' => 'PL', 'en' => 'EN', 'fr' => 'FR'],
        slugSourceLocale: 'pl',
    );

    /** @var TranslatableFields $translatable */
    $translatable = $group->getDefaultChildComponents()[0];

    expect($translatable->getTranslatableTabModifiers())->not->toBeEmpty()
        ->and($translatable->getTranslatableFieldModifiers())->not->toBeEmpty();
});
