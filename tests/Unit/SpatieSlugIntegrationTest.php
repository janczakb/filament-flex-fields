<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Bjanczak\FilamentFlexFields\Support\Slug\SpatieSlugIntegration;
use Bjanczak\FilamentFlexFields\Tests\Support\SlugFieldPost;
use Bjanczak\FilamentFlexFields\Tests\Support\SlugFieldPostWithSkipWhen;
use Bjanczak\FilamentFlexFields\Tests\Support\SlugFieldSelfHealingPost;
use Bjanczak\FilamentFlexFields\Tests\Support\SlugFieldSpatieAttributePost;
use Bjanczak\FilamentFlexFields\Tests\Support\SlugFieldSpatieMultiAttributePost;
use Bjanczak\FilamentFlexFields\Tests\Support\SlugFieldSpatiePost;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableSlugForm;
use Filament\Forms\Components\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Sluggable\Actions\GenerateSlugAction;
use Spatie\Sluggable\SlugOptions;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    TestableSlugForm::$formSchema = [];
    TestableSlugForm::$modelClass = null;

    Schema::dropIfExists('slug_field_posts');

    Schema::create('slug_field_posts', function (Blueprint $table): void {
        $table->id();
        $table->string('title')->nullable();
        $table->string('subtitle')->nullable();
        $table->string('slug')->nullable();
        $table->unsignedBigInteger('tenant_id')->nullable();
        $table->timestamps();
    });
});

it('detects spatie sluggable when installed', function (): void {
    expect(SpatieSlugIntegration::isAvailable())->toBeTrue();
});

it('generates slugs via spatie generate slug action from getSlugOptions', function (): void {
    $model = new SlugFieldSpatiePost([
        'title' => 'Luxury Yacht',
        'subtitle' => 'Mediterranean',
    ]);

    $slug = SpatieSlugIntegration::generate(
        'Luxury Yacht',
        $model,
        'slug',
        'title',
        ['subtitle' => 'Mediterranean'],
    );

    expect($slug)->toBe('luxury-yacht-mediterranean');
});

it('generates slugs via spatie attribute configuration', function (): void {
    $model = new SlugFieldSpatieAttributePost([
        'title' => 'Hello World',
    ]);

    $slug = SpatieSlugIntegration::generate('Hello World', $model);

    expect($slug)->toBe('hello_world');
});

it('respects spatie skipGenerateWhen during preview', function (): void {
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

    $slug = SpatieSlugIntegration::generate('New Title', $model);

    expect($slug)->toBe('keep-me');
});

it('respects spatie preventOverwrite during preview', function (): void {
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

    $slug = SpatieSlugIntegration::generate('Brand New Title', $model);

    expect($slug)->toBe('existing-slug');
});

it('applies spatie extraScope when resolving unique suffixes', function (): void {
    SlugFieldSpatiePost::create([
        'title' => 'Collision',
        'slug' => 'collision',
        'tenant_id' => 1,
    ]);

    $scopedModel = new class extends SlugFieldPost
    {
        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom('title')
                ->saveSlugsTo('slug')
                ->extraScope(fn ($query) => $query->where('tenant_id', 2));
        }
    };

    $scopedModel->setAttribute('title', 'Collision');
    $scopedModel->setAttribute('tenant_id', 2);

    $slug = SpatieSlugIntegration::generate('Collision', $scopedModel);

    expect($slug)->toBe('collision');
});

it('uses spatie closure source fields for preview generation', function (): void {
    $model = new class extends SlugFieldPost
    {
        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom(fn (SlugFieldPost $model): string => "{$model->title}-{$model->subtitle}")
                ->saveSlugsTo('slug');
        }
    };

    $model->setAttribute('title', 'Yacht');
    $model->setAttribute('subtitle', 'Charter');

    $slug = SpatieSlugIntegration::generate('Yacht', $model, 'slug', 'title', [
        'subtitle' => 'Charter',
    ]);

    expect($slug)->toBe('yacht-charter');
});

it('uses spatie startSlugSuffixFrom for unique collisions', function (): void {
    SlugFieldSpatiePost::create([
        'title' => 'Taken',
        'slug' => 'taken',
    ]);

    $model = new class extends SlugFieldPost
    {
        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom('title')
                ->saveSlugsTo('slug')
                ->startSlugSuffixFrom(5);
        }
    };

    $model->setAttribute('title', 'Taken');

    $slug = SpatieSlugIntegration::generate('Taken', $model);

    expect($slug)->toBe('taken-5');
});

it('routes slug field generation through spatie when model is configured', function (): void {
    $field = SlugField::make('slug')
        ->spatieModel(SlugFieldSpatieAttributePost::class)
        ->slugSeparator('-');

    expect($field->usesSpatieIntegration())->toBeTrue()
        ->and($field->generateSlugFromSource('Spatie Powered Title'))->toBe('spatie-powered-title');
});

it('falls back to slug generator when spatie options are unavailable', function (): void {
    $model = new SlugFieldPost;

    expect(SpatieSlugIntegration::generate('Fallback Title', $model))->toBe('fallback-title');
});

it('collects sibling form fields for spatie multi-source slug generation in livewire', function (): void {
    TestableSlugForm::$formSchema = [
        TitleSlugField::make(spatieModel: SlugFieldSpatiePost::class),
        FlexTextInput::make('subtitle')->live(),
    ];
    TestableSlugForm::$modelClass = SlugFieldSpatiePost::class;

    Livewire::test(TestableSlugForm::class)
        ->set('data.subtitle', 'Mediterranean')
        ->set('data.title', 'Luxury Yacht')
        ->assertSet('data.slug', 'luxury-yacht-mediterranean');
});

it('collects sibling form fields for standalone slug field spatie preview', function (): void {
    TestableSlugForm::$formSchema = [
        FlexTextInput::make('title')->live(),
        FlexTextInput::make('subtitle')->live(),
        SlugField::make('slug')
            ->source('title')
            ->spatieModel(SlugFieldSpatiePost::class),
    ];
    TestableSlugForm::$modelClass = SlugFieldSpatiePost::class;

    $component = Livewire::test(TestableSlugForm::class)
        ->set('data.title', 'Luxury Yacht')
        ->set('data.subtitle', 'Mediterranean');

    $slugField = $component->instance()->getSchema('form')->getComponent('slug');
    expect($slugField)->toBeInstanceOf(SlugField::class)
        ->and($slugField->generateSlugFromSource('Luxury Yacht'))->toBe('luxury-yacht-mediterranean');
});

it('resolves spatie slug options from v4 sluggable attribute without getSlugOptions', function (): void {
    $options = SpatieSlugIntegration::resolveSlugOptionsForModelClass(SlugFieldSpatieAttributePost::class);

    expect($options)->not->toBeNull()
        ->and($options->slugField)->toBe('slug')
        ->and($options->slugSeparator)->toBe('_');
});

it('resolves multi-field spatie attribute sources for sibling collection', function (): void {
    $field = SlugField::make('slug')
        ->source('title')
        ->spatieModel(SlugFieldSpatieMultiAttributePost::class);

    TestableSlugForm::$formSchema = [
        FlexTextInput::make('title')->live(),
        FlexTextInput::make('subtitle')->live(),
        $field,
    ];
    TestableSlugForm::$modelClass = SlugFieldSpatieMultiAttributePost::class;

    $component = Livewire::test(TestableSlugForm::class)
        ->set('data.title', 'Luxury Yacht')
        ->set('data.subtitle', 'Mediterranean');

    $slugField = $component->instance()->getSchema('form')->getComponent('slug');

    expect($slugField->usesSpatieIntegration())->toBeTrue()
        ->and($slugField->generateSlugFromSource('Luxury Yacht'))->toBe('luxury-yacht-mediterranean');
});

it('uses spatie suffix generator for unique collisions', function (): void {
    SlugFieldPost::create([
        'title' => 'Taken',
        'slug' => 'taken',
    ]);

    $model = new class extends SlugFieldPost
    {
        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom('title')
                ->saveSlugsTo('slug')
                ->usingSuffixGenerator(fn (string $slug, int $iteration): string => 'v'.($iteration + 1));
        }
    };

    $model->setAttribute('title', 'Taken');

    $slug = SpatieSlugIntegration::generate('Taken', $model);

    expect($slug)->toBe('taken-v1');
});

it('uses spatie suffix on first occurrence when enabled', function (): void {
    $model = new class extends SlugFieldPost
    {
        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom('title')
                ->saveSlugsTo('slug')
                ->useSuffixOnFirstOccurrence()
                ->startSlugSuffixFrom(2);
        }
    };

    $model->setAttribute('title', 'Unique Title');

    $slug = SpatieSlugIntegration::generate('Unique Title', $model);

    expect($slug)->toBe('unique-title-2');
});

it('uses configured spatie generate slug action from sluggable config', function (): void {
    config()->set('sluggable.actions.generate_slug', GenerateSlugAction::class);

    $model = new SlugFieldSpatiePost([
        'title' => 'Configured Action',
        'subtitle' => 'Works',
    ]);

    $slug = SpatieSlugIntegration::generate(
        'Configured Action',
        $model,
        'slug',
        'title',
        ['subtitle' => 'Works'],
    );

    expect($slug)->toBe('configured-action-works');
});

it('detects self-healing urls from spatie slug options', function (): void {
    $model = new SlugFieldSelfHealingPost([
        'title' => 'Hello World',
        'slug' => 'hello-world',
    ]);
    $model->setAttribute('id', 5);

    expect(SpatieSlugIntegration::usesSelfHealingUrls($model))->toBeTrue()
        ->and(SpatieSlugIntegration::getSelfHealingSeparator($model))->toBe('-')
        ->and(SpatieSlugIntegration::buildSelfHealingRouteKey('hello-world', $model))->toBe('hello-world-5');
});

it('builds self-healing route keys with custom separator', function (): void {
    $model = new class extends SlugFieldPost
    {
        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom('title')
                ->saveSlugsTo('slug')
                ->selfHealing('--');
        }
    };

    $model->setAttribute('id', 5);

    expect(SpatieSlugIntegration::getSelfHealingSeparator($model))->toBe('--')
        ->and(SpatieSlugIntegration::buildSelfHealingRouteKey('hello-world', $model))->toBe('hello-world--5');
});

it('returns raw slug when self-healing is disabled', function (): void {
    $model = new SlugFieldSpatiePost([
        'title' => 'Hello World',
        'slug' => 'hello-world',
    ]);
    $model->setAttribute('id', 5);

    expect(SpatieSlugIntegration::usesSelfHealingUrls($model))->toBeFalse()
        ->and(SpatieSlugIntegration::buildSelfHealingRouteKey('hello-world', $model))->toBe('hello-world');
});

it('builds self-healing permalink urls for spatie models on edit', function (): void {
    $record = SlugFieldSelfHealingPost::create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
    ]);

    TestableSlugForm::$formSchema = [
        SlugField::make('slug')
            ->source('title')
            ->urlHost('https://example.com')
            ->spatieModel(SlugFieldSelfHealingPost::class),
    ];
    TestableSlugForm::$modelClass = SlugFieldSelfHealingPost::class;

    $component = Livewire::test(TestableSlugForm::class, ['record' => $record]);
    $slugField = $component->instance()->getSchema('form')->getComponent('slug');

    expect($slugField)->toBeInstanceOf(SlugField::class)
        ->and($slugField->usesSelfHealingPermalink())->toBeTrue()
        ->and($slugField->getFullPermalinkUrl('hello-world'))->toBe('https://example.com/hello-world-'.$record->getKey())
        ->and($slugField->getAlpineConfiguration()['selfHealingPermalink'])->toBeTrue()
        ->and($slugField->getAlpineConfiguration()['permalinkRecordKey'])->toBe($record->getKey())
        ->and($slugField->getAlpineConfiguration()['selfHealingSeparator'])->toBe('-');
});

it('passes route key to visit url closure for self-healing models', function (): void {
    $record = SlugFieldSelfHealingPost::create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
    ]);

    TestableSlugForm::$formSchema = [
        SlugField::make('slug')
            ->source('title')
            ->urlHost('https://example.com')
            ->spatieModel(SlugFieldSelfHealingPost::class)
            ->visitUrl(fn (string $slug, string $routeKey, ?Model $record): string => "https://example.com/visit/{$routeKey}"),
    ];
    TestableSlugForm::$modelClass = SlugFieldSelfHealingPost::class;

    $component = Livewire::test(TestableSlugForm::class, ['record' => $record]);
    $slugField = $component->instance()->getSchema('form')->getComponent('slug');

    expect($slugField->getFullPermalinkUrl('hello-world'))
        ->toBe('https://example.com/visit/hello-world-'.$record->getKey());
});

it('hydrates spatie preview from hidden form fields for extraScope', function (): void {
    SlugFieldSpatiePost::create([
        'title' => 'Collision',
        'slug' => 'collision',
        'tenant_id' => 1,
    ]);

    $scopedModelClass = new class extends SlugFieldPost
    {
        public function getSlugOptions(): SlugOptions
        {
            return SlugOptions::create()
                ->generateSlugsFrom('title')
                ->saveSlugsTo('slug')
                ->extraScope(fn ($query) => $query->where('tenant_id', $this->tenant_id));
        }
    };

    TestableSlugForm::$formSchema = [
        Hidden::make('tenant_id')->default(2),
        FlexTextInput::make('title')->live(),
        SlugField::make('slug')
            ->source('title')
            ->spatieModel($scopedModelClass::class),
    ];
    TestableSlugForm::$modelClass = $scopedModelClass::class;

    $component = Livewire::test(TestableSlugForm::class)
        ->set('data.tenant_id', 2)
        ->set('data.title', 'Collision');

    $slugField = $component->instance()->getSchema('form')->getComponent('slug');

    expect($slugField->generateSlugFromSource('Collision'))->toBe('collision');
});

it('respects spatie skipGenerateWhen using hydrated form state', function (): void {
    TestableSlugForm::$formSchema = [
        Hidden::make('status')->default('published'),
        FlexTextInput::make('title')->live(),
        SlugField::make('slug')
            ->source('title')
            ->spatieModel(SlugFieldPostWithSkipWhen::class),
    ];
    TestableSlugForm::$modelClass = SlugFieldPostWithSkipWhen::class;

    $component = Livewire::test(TestableSlugForm::class, [
        'record' => SlugFieldPostWithSkipWhen::create([
            'title' => 'Original',
            'slug' => 'keep-me',
        ]),
    ])->set('data.status', 'published')
        ->set('data.title', 'Brand New Title');

    $slugField = $component->instance()->getSchema('form')->getComponent('slug');

    expect($slugField->generateSlugFromSource('Brand New Title'))->toBe('keep-me');
});
