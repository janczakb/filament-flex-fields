<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Bjanczak\FilamentFlexFields\Tests\Support\SlugFieldPost;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableSlugForm;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    TestableSlugForm::$formSchema = [];
    TestableSlugForm::$modelClass = null;

    Schema::dropIfExists('slug_field_posts');

    Schema::create('slug_field_posts', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->string('slug')->unique();
        $table->timestamps();
    });
});

it('auto-generates slug from title on create', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(),
    ];

    Livewire::test(TestableSlugForm::class)
        ->set('data.title', 'Hello World')
        ->assertSet('data.slug', 'hello-world');
});

it('preserves slug when editing an existing record', function (): void {
    $post = SlugFieldPost::create([
        'title' => 'Original Title',
        'slug' => 'original-slug',
    ]);

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(),
    ];

    Livewire::test(TestableSlugForm::class, ['record' => $post])
        ->set('data.title', 'Updated Title')
        ->assertSet('data.slug', 'original-slug');
});

it('stops auto sync after slug is manually edited', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(),
    ];

    Livewire::test(TestableSlugForm::class)
        ->set('data.slug', 'custom-slug')
        ->set('data.slug_auto_update_disabled', true)
        ->set('data.title', 'Another Title')
        ->assertSet('data.slug', 'custom-slug');
});

it('validates duplicate slugs through scoped unique rules', function (): void {
    SlugFieldPost::create([
        'title' => 'First Post',
        'slug' => 'hello-world',
    ]);

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(),
    ];

    $component = Livewire::test(TestableSlugForm::class)
        ->set('data.title', 'Hello World');

    try {
        $component->instance()->getSchema('form')->validate();
        expect(false)->toBeTrue('Expected duplicate slug validation to fail');
    } catch (ValidationException $exception) {
        expect($exception->errors()['data.slug'][0])
            ->toBe(__('filament-flex-fields::default.validation.slug.unique'));
    }
});

it('reports taken slug through live uniqueness check', function (): void {
    SlugFieldPost::create([
        'title' => 'First Post',
        'slug' => 'taken-slug',
    ]);

    TestableSlugForm::$formSchema = [
        SlugField::make('slug'),
    ];

    $livewire = Livewire::test(TestableSlugForm::class);

    $field = collect($livewire->instance()->getSchema('form')->getFlatFields(withHidden: true))
        ->first(fn ($component) => $component instanceof SlugField);

    expect($field)->not->toBeNull()
        ->and($field->checkSlugUniqueness('taken-slug'))->toBe([
            'available' => false,
            'message' => __('filament-flex-fields::default.validation.slug.unique'),
        ])
        ->and($field->checkSlugUniqueness('free-slug'))->toBe([
            'available' => true,
            'message' => null,
        ]);
});

it('can disable live uniqueness validation', function (): void {
    $field = SlugField::make('slug')->liveUniqueValidation(false);

    expect($field->shouldValidateSlugUniquenessLive())->toBeFalse();
});

it('blocks form save when slug inline edit is pending', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(),
    ];

    $component = Livewire::test(TestableSlugForm::class)
        ->set('data.slug_inline_edit_pending', true)
        ->set('data.title', 'Pending Slug Title')
        ->set('data.slug', 'pending-slug-title');

    try {
        $component->instance()->getSchema('form')->validate();
        expect(false)->toBeTrue('Expected validation to fail for pending inline edit');
    } catch (ValidationException $exception) {
        expect($exception->errors()['data.slug'][0])
            ->toBe(__('filament-flex-fields::default.validation.slug.inline_edit_pending'));
    }
});

it('allows form save after slug inline edit is confirmed', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(),
    ];

    Livewire::test(TestableSlugForm::class)
        ->set('data.title', 'Brand New Post')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('data.slug', 'brand-new-post');
});

it('supports title autofocus and title rules', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            titleAutofocus: true,
            titleRules: ['required', 'string', 'min:3'],
        ),
    ];

    $component = Livewire::test(TestableSlugForm::class)
        ->set('data.title', 'ab');

    expect(fn () => $component->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('fires title after state updated callback', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            titleAfterStateUpdated: function (Set $set): void {
                $set('slug', 'callback-slug');
            },
        ),
    ];

    Livewire::test(TestableSlugForm::class)
        ->set('data.title', 'Trigger Title')
        ->assertSet('data.slug', 'callback-slug');
});

it('reads default field names from config', function (): void {
    config()->set('filament-flex-fields.slug.field_title', 'name');
    config()->set('filament-flex-fields.slug.field_slug', 'handle');

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(),
    ];

    Livewire::test(TestableSlugForm::class)
        ->set('data.name', 'Configured Title')
        ->assertSet('data.handle', 'configured-title');
});
