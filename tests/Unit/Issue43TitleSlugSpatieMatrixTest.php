<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Bjanczak\FilamentFlexFields\Support\Slug\SpatieSlugIntegration;
use Bjanczak\FilamentFlexFields\Tests\Support\SlugFieldPost;
use Bjanczak\FilamentFlexFields\Tests\Support\SlugFieldSpatiePost;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableSlugForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    TestableSlugForm::$formSchema = [];
    TestableSlugForm::$modelClass = null;

    Schema::dropIfExists('slug_field_posts');

    Schema::create('slug_field_posts', function (Blueprint $table): void {
        $table->id();
        $table->string('title')->nullable();
        $table->string('name')->nullable();
        $table->string('subtitle')->nullable();
        $table->string('slug')->nullable();
        $table->string('renamed_slug')->nullable();
        $table->unsignedBigInteger('tenant_id')->nullable();
        $table->timestamps();
    });
});

function issue43GenreModel(): Model
{
    return new class extends SlugFieldPost
    {
        use HasSlug;

        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom('name')
                ->saveSlugsTo('slug');
        }
    };
}

function typeTitleCharacterByCharacter(object $component, string $field, string $title): void
{
    $typed = '';

    foreach (mb_str_split($title) as $char) {
        $typed .= $char;
        $component->set('data.'.$field, $typed);
    }
}

it('matches GitHub #43 GenreResource: name to slug with spatieModel stays in sync while typing', function (): void {
    $model = issue43GenreModel();

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'name',
            fieldSlug: 'slug',
            urlPath: '/genre/',
            spatieModel: $model::class,
        ),
    ];
    TestableSlugForm::$modelClass = $model::class;

    $component = Livewire::test(TestableSlugForm::class);
    typeTitleCharacterByCharacter($component, 'name', 'Action');

    $component->assertSet('data.slug', 'action');
});

it('keeps multi-word titles slugified after every keystroke with Spatie', function (): void {
    $model = issue43GenreModel();

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'name',
            fieldSlug: 'slug',
            spatieModel: $model::class,
        ),
    ];
    TestableSlugForm::$modelClass = $model::class;

    $component = Livewire::test(TestableSlugForm::class);
    typeTitleCharacterByCharacter($component, 'name', 'Science Fiction');

    $component->assertSet('data.slug', 'science-fiction');
});

it('keeps Polish diacritics slugified after every keystroke with Spatie', function (): void {
    $model = issue43GenreModel();

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'name',
            fieldSlug: 'slug',
            spatieModel: $model::class,
        ),
    ];
    TestableSlugForm::$modelClass = $model::class;

    $component = Livewire::test(TestableSlugForm::class);
    typeTitleCharacterByCharacter($component, 'name', 'Łódź Jazz');

    $component->assertSet('data.slug', 'lodz-jazz');
});

it('still works when fieldSlug is renamed away from slug (reporter workaround)', function (): void {
    $model = issue43GenreModel();

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'name',
            fieldSlug: 'renamed_slug',
            urlPath: '/genre/',
            spatieModel: $model::class,
        ),
    ];
    TestableSlugForm::$modelClass = $model::class;

    $component = Livewire::test(TestableSlugForm::class);
    typeTitleCharacterByCharacter($component, 'name', 'Action');

    $component->assertSet('data.renamed_slug', 'action');
});

it('does not freeze when Spatie generateSlugsFrom incorrectly points at slug (reporter workaround)', function (): void {
    $model = new class extends SlugFieldPost
    {
        use HasSlug;

        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom('slug')
                ->saveSlugsTo('slug');
        }
    };

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'name',
            fieldSlug: 'slug',
            spatieModel: $model::class,
        ),
    ];
    TestableSlugForm::$modelClass = $model::class;

    $component = Livewire::test(TestableSlugForm::class);
    typeTitleCharacterByCharacter($component, 'name', 'Action');

    expect((string) $component->get('data.slug'))->not->toBe('a')
        ->and(strlen((string) $component->get('data.slug')))->toBeGreaterThan(1);
});

it('works without spatieModel like Playground one-liner', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'name',
            fieldSlug: 'slug',
            urlPath: '/genre/',
        ),
    ];
    TestableSlugForm::$modelClass = SlugFieldPost::class;

    $component = Livewire::test(TestableSlugForm::class);
    typeTitleCharacterByCharacter($component, 'name', 'Action');

    $component->assertSet('data.slug', 'action');
});

it('still respects preventOverwrite during Spatie preview after the live-typing fix', function (): void {
    $model = new class extends SlugFieldPost
    {
        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom('title')
                ->saveSlugsTo('slug')
                ->preventOverwrite();
        }
    };

    $model->setAttribute('slug', 'existing-slug');

    expect(SpatieSlugIntegration::generate(
        'Brand New Title',
        $model,
        'slug',
        'title',
        ['slug' => 'stale-from-form'],
    ))->toBe('existing-slug');
});

it('still respects skipGenerateWhen during Spatie preview after the live-typing fix', function (): void {
    $model = new class extends SlugFieldPost
    {
        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom('title')
                ->saveSlugsTo('slug')
                ->skipGenerateWhen(fn (): bool => true);
        }
    };

    $model->setAttribute('slug', 'keep-me');

    expect(SpatieSlugIntegration::generate(
        'New Title',
        $model,
        'slug',
        'title',
        ['slug' => 'should-be-ignored'],
    ))->toBe('keep-me');
});

it('still collects sibling Spatie sources while ignoring stale form slug', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(spatieModel: SlugFieldSpatiePost::class),
        FlexTextInput::make('subtitle')->live(),
    ];
    TestableSlugForm::$modelClass = SlugFieldSpatiePost::class;

    Livewire::test(TestableSlugForm::class)
        ->set('data.subtitle', 'Mediterranean')
        ->set('data.title', 'L')
        ->set('data.title', 'Lu')
        ->set('data.title', 'Lux')
        ->set('data.title', 'Luxury Yacht')
        ->assertSet('data.slug', 'luxury-yacht-mediterranean');
});

it('regenerate path still works after auto-sync was used character by character', function (): void {
    $model = issue43GenreModel();

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            fieldTitle: 'name',
            fieldSlug: 'slug',
            spatieModel: $model::class,
        ),
    ];
    TestableSlugForm::$modelClass = $model::class;

    $component = Livewire::test(TestableSlugForm::class);
    typeTitleCharacterByCharacter($component, 'name', 'Horror');

    $slugField = $component->instance()->getSchema('form')->getComponent('slug');
    expect($slugField)->toBeInstanceOf(SlugField::class)
        ->and($slugField->generateSlugFromSource('Horror Comedy'))->toBe('horror-comedy');
});

it('unit-level Spatie generate ignores stale slug form attribute on every call', function (): void {
    $model = issue43GenreModel();
    $expected = [];
    $typed = '';

    foreach (str_split('Thriller') as $char) {
        $typed .= $char;
        $expected[$typed] = SpatieSlugIntegration::generate(
            $typed,
            new ($model::class),
            'slug',
            'name',
            [
                'name' => $typed,
                'slug' => $expected[substr($typed, 0, -1)] ?? 'x',
            ],
        );
    }

    expect($expected['T'])->toBe('t')
        ->and($expected['Th'])->toBe('th')
        ->and($expected['Thr'])->toBe('thr')
        ->and($expected['Thriller'])->toBe('thriller');
});
