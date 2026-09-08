<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Bjanczak\FilamentFlexFields\Tests\Support\TranslatablePost;
use Composer\InstalledVersions;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Concerns\HasChildComponents;
use Filament\Schemas\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema as DbSchema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

$filamentSchemasVersion = InstalledVersions::getVersion('filament/schemas') ?? '0.0.0';
$isFilamentSchemas58OrNewer = version_compare($filamentSchemasVersion, '5.8.0', '>=');

beforeEach(function (): void {
    TestableTranslatableForm::$formSchema = [];

    DbSchema::dropIfExists('translatable_posts');

    DbSchema::create('translatable_posts', function (Blueprint $table): void {
        $table->id();
        $table->json('title');
        $table->json('body')->nullable();
        $table->timestamps();
    });
});

it('runs against Filament schemas 5.8+ where Component::schema accepts Schema', function (): void {
    $version = InstalledVersions::getVersion('filament/schemas');

    expect($version)->not->toBeNull()
        ->and(version_compare((string) $version, '5.8.0', '>='))->toBeTrue();

    $method = new ReflectionMethod(HasChildComponents::class, 'schema');
    $parameterType = $method->getParameters()[0]->getType();

    expect($parameterType)->toBeInstanceOf(ReflectionUnionType::class);

    $types = array_map(
        static fn (ReflectionNamedType $type): string => $type->getName(),
        array_values(array_filter(
            $parameterType->getTypes(),
            static fn ($type): bool => $type instanceof ReflectionNamedType,
        )),
    );

    expect($types)->toContain(Schema::class)
        ->and($types)->toContain('array')
        ->and($types)->toContain(Closure::class);
})->skip(! $isFilamentSchemas58OrNewer, 'Requires Filament schemas >= 5.8.0');

it('accepts Schema instances and Closures that return Schema without fatal LSP errors', function (): void {
    $fromInstance = TranslatableFields::make('Content')
        ->locales(['en' => 'English'])
        ->schema(Schema::make()->components([
            FlexTextInput::make('title'),
        ]));

    $fromClosure = TranslatableFields::make('Content')
        ->locales(['pl' => 'PL', 'en' => 'EN'])
        ->schema(fn (): Schema => Schema::make()->components([
            FlexTextInput::make('title'),
        ]));

    expect($fromInstance->buildTranslatableTabs())->toHaveCount(1)
        ->and($fromClosure->buildTranslatableTabs())->toHaveCount(2);
});

it('keeps child-schema resolution stable across Filament 5.8 cached getChildSchema hits', function (): void {
    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Title')
            ->locales(['pl' => 'PL', 'en' => 'EN'])
            ->modifyFieldsUsing(function (Field $field, string $locale): void {
                $field->placeholder("Title ({$locale})");
            })
            ->schema([
                FlexTextInput::make('title')->hiddenLabel(),
            ]),
    ];

    $translatable = Livewire::test(TestableTranslatableForm::class)
        ->instance()
        ->getSchema('form')
        ->getComponents()[0];

    expect($translatable)->toBeInstanceOf(TranslatableFields::class);

    $resolutions = [
        $translatable->getChildSchema(),
        $translatable->getChildSchema(),
        $translatable->getChildSchema(),
    ];

    foreach ($resolutions as $schema) {
        expect($schema)->not->toBeNull()
            ->and($schema->getComponents())->toHaveCount(2);

        $locales = array_map(
            static fn (TranslatableTab $tab): string => $tab->getLocale(),
            array_values(array_filter(
                $schema->getComponents(),
                static fn ($tab): bool => $tab instanceof TranslatableTab,
            )),
        );

        expect($locales)->toBe(['pl', 'en']);

        foreach ($schema->getComponents() as $tab) {
            expect($tab)->toBeInstanceOf(TranslatableTab::class);

            $field = $tab->getChildSchema()->getComponents()[0];

            expect($field)->toBeInstanceOf(Field::class)
                ->and($field->getPlaceholder())->toBe("Title ({$tab->getLocale()})");
        }
    }

    $translatable->clearCachedChildSchemas();

    $afterClear = $translatable->getChildSchema();

    expect($afterClear)->not->toBeNull()
        ->and($afterClear->getComponents())->toHaveCount(2)
        ->and($afterClear->getComponents()[0])->toBeInstanceOf(TranslatableTab::class)
        ->and($afterClear->getComponents()[0]->getLocale())->toBe('pl');
});

it('rebuilds locale tabs after schema() invalidates Filament child-schema caches', function (): void {
    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Title')
            ->locales(['en' => 'English'])
            ->schema([
                FlexTextInput::make('title')->hiddenLabel(),
            ]),
    ];

    $component = Livewire::test(TestableTranslatableForm::class)
        ->instance()
        ->getSchema('form')
        ->getComponents()[0];

    expect($component->getChildSchema()->getComponents())->toHaveCount(1);

    $component
        ->locales(['pl' => 'PL', 'en' => 'EN'])
        ->schema([
            FlexTextInput::make('title')->hiddenLabel(),
            FlexTextInput::make('subtitle')->hiddenLabel(),
        ]);

    $tabs = $component->getChildSchema()->getComponents();

    expect($tabs)->toHaveCount(2)
        ->and($tabs[0]->getChildSchema()->getComponents())->toHaveCount(2)
        ->and($tabs[1]->getChildSchema()->getComponents())->toHaveCount(2);
});

it('hydrates JSON locale state through fillStateWithNull after Filament 5.8 schema caching', function (): void {
    $post = TranslatablePost::create([
        'title' => ['pl' => 'Witaj', 'en' => 'Hello'],
        'body' => null,
    ]);

    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Title')
            ->locales(['pl' => 'PL', 'en' => 'EN'])
            ->schema([
                FlexTextInput::make('title')->hiddenLabel(),
            ]),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class, ['record' => $post])
        ->assertSet('data.title.pl', 'Witaj')
        ->assertSet('data.title.en', 'Hello');

    $translatable = $livewire->instance()->getSchema('form')->getComponents()[0];

    $translatable->getChildSchema();
    $translatable->getChildSchema();
    $translatable->fillStateWithNull();

    $livewire
        ->assertSet('data.title.pl', 'Witaj')
        ->assertSet('data.title.en', 'Hello');
});

it('supports Schema templates inside a Livewire form on Filament 5.8', function (): void {
    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Title')
            ->locales(['ar' => 'Arabic', 'en' => 'English'])
            ->schema(Schema::make()->components([
                FlexTextInput::make('title')->hiddenLabel(),
            ])),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->set('data.title.ar', 'مرحبا')
        ->set('data.title.en', 'Hello')
        ->assertSet('data.title.ar', 'مرحبا')
        ->assertSet('data.title.en', 'Hello');
});
