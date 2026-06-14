<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\Slug\SlugGenerator;
use Bjanczak\FilamentFlexFields\Support\Slug\SpatieSlugIntegration;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableSlugForm;
use Filament\Schemas\Components\FusedGroup;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

it('exposes slug field configuration api', function () {
    $field = SlugField::make('slug')
        ->size('lg')
        ->variant('primary')
        ->source('title')
        ->sourceLive(false)
        ->slugSeparator('_')
        ->maxSlugLength(80)
        ->urlHost('https://example.com')
        ->urlPath('/blog/')
        ->permalinkPreview()
        ->showVisitLink(false)
        ->showCopyButton(false)
        ->showRegenerateButton(false)
        ->autoGenerate(false)
        ->preserveSlugOnEdit(false)
        ->inlineEditing(false)
        ->allowHomepageSlug()
        ->generationDebounce(600)
        ->slugReadOnly()
        ->titleReadOnly()
        ->slugUnique(false)
        ->spatieModel('App\\Models\\Post')
        ->spatieSlugField('permalink')
        ->slugLabelPostfix('/detail/')
        ->recordSlug('existing-slug');

    expect($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('primary')
        ->and($field->getSourceStatePath())->toBe('title')
        ->and($field->isSourceLive())->toBeFalse()
        ->and($field->getSeparator())->toBe('_')
        ->and($field->getMaxSlugLength())->toBe(80)
        ->and($field->getUrlHost())->toBe('https://example.com')
        ->and($field->getUrlPath())->toBe('/blog/')
        ->and($field->hasPermalinkPreview())->toBeTrue()
        ->and($field->shouldShowVisitLink())->toBeFalse()
        ->and($field->shouldShowCopyButton())->toBeFalse()
        ->and($field->shouldShowRegenerateButton())->toBeFalse()
        ->and($field->shouldAutoGenerate())->toBeFalse()
        ->and($field->shouldPreserveSlugOnEdit())->toBeFalse()
        ->and($field->shouldUseInlineEditing())->toBeFalse()
        ->and($field->allowsHomepageSlug())->toBeTrue()
        ->and($field->getDebounceMilliseconds())->toBe(600)
        ->and($field->isSlugReadOnly())->toBeTrue()
        ->and($field->isTitleReadOnly())->toBeTrue()
        ->and($field->shouldEnforceSlugUnique())->toBeFalse()
        ->and($field->getSpatieModelClass())->toBe('App\\Models\\Post')
        ->and($field->getSpatieSlugField())->toBe('permalink')
        ->and($field->getSlugLabelPostfix())->toBe('/detail/')
        ->and($field->getRecordSlug())->toBe('existing_slug')
        ->and($field->usesSpatieIntegration())->toBeFalse();
});

it('resolves nested source state paths for slug field', function () {
    $field = SlugField::make('data.slug')->source('title');

    expect($field->getSourceStatePath())->toBe('data.title');
});

it('normalizes slug values and supports homepage slug', function () {
    $field = SlugField::make('slug')->slugSeparator('-')->allowHomepageSlug();

    expect($field->normalizeSlug('  Hello--World!! '))->toBe('hello-world')
        ->and($field->normalizeSlug('/'))->toBe('/')
        ->and($field->generateSlugFromSource('Luxury Yacht Charter'))->toBe('luxury-yacht-charter');
});

it('keeps homepage slash as fixed chrome instead of editable input value', function () {
    expect(SlugGenerator::toEditableSlug('/', true))->toBe('')
        ->and(SlugGenerator::fromEditableSlug('', '-', true))->toBe('/')
        ->and(SlugGenerator::fromEditableSlug('about', '-', true))->toBe('about')
        ->and(SlugGenerator::normalizeEditableSlug('/about'))->toBe('about')
        ->and(SlugGenerator::normalizeEditableSlug('//'))->toBe('')
        ->and(SlugGenerator::permalinkSlugSegment('test', null, true))->toBe('/test')
        ->and(SlugGenerator::permalinkSlugSegment('/', null, true))->toBe('')
        ->and(SlugGenerator::permalinkSlugSegment('test', '/blog/', true))->toBe('test')
        ->and(SlugGenerator::shouldShowPermalinkSlugSeparator(null, 'https://wyachts.test'))->toBeTrue()
        ->and(SlugGenerator::shouldShowPermalinkSlugSeparator('/blog/', 'https://wyachts.test'))->toBeFalse();

    $field = SlugField::make('slug')
        ->allowHomepageSlug()
        ->urlHost('https://wyachts.test');

    expect($field->getFullPermalinkUrl('test'))->toBe('https://wyachts.test/test')
        ->and($field->getFullPermalinkUrl('/'))->toBe('https://wyachts.test');

    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/slug-field.blade.php');
    $js = file_get_contents(__DIR__.'/../../resources/js/components/slug-field.js');
    $utils = file_get_contents(__DIR__.'/../../resources/js/support/slug-utils.js');

    expect($blade)
        ->toContain('class="fff-slug-field__slug-separator"')
        ->and($blade)->toContain('$showInitialSlugSeparator')
        ->and($blade)->toContain('x-bind:value="editableSlugValue()"');

    expect($js)
        ->toContain('slugPathSeparatorVisible()')
        ->and($js)->toContain('permalinkSlugSegment()')
        ->and($js)->toContain('resolvePermalinkSlugForUrl')
        ->and($js)->toContain('buildSelfHealingRouteKey')
        ->and($js)->toContain('fromEditableSlug');

    expect($utils)
        ->toContain('normalizeEditableSlug')
        ->and($utils)->toContain('fromEditableSlug')
        ->and($utils)->toContain('toEditableSlug');
});

it('builds full permalink urls with postfix', function () {
    $field = SlugField::make('slug')
        ->urlHost('https://wyachts.test')
        ->urlPath('/charters/')
        ->slugLabelPostfix('/preview');

    expect($field->getFullPermalinkUrl('mediterranean-sailing'))
        ->toBe('https://wyachts.test/charters/mediterranean-sailing/preview')
        ->and($field->getDisplayUrlHost())->toBe('wyachts.test');
});

it('exposes alpine configuration for the field view', function () {
    $field = SlugField::make('slug')
        ->source('title')
        ->urlHost('https://wyachts.test')
        ->urlPath('/blog/')
        ->recordSlug('stored-slug');

    $config = $field->getAlpineConfiguration();

    expect($config)->toHaveKeys(['sourcePath', 'labels', 'preserveOnEdit', 'inlineEditing', 'recordSlug', 'showActionButtonLabels', 'initialAutoSyncDisabled', 'canVisitLink'])
        ->and($config['sourcePath'])->toBe('title')
        ->and($config['recordSlug'])->toBe('stored-slug')
        ->and($config['initialAutoSyncDisabled'])->toBeTrue()
        ->and($config['showActionButtonLabels'])->toBeTrue()
        ->and($config['canVisitLink'])->toBeFalse()
        ->and($config['labels']['edit'])->toBe('Edit');
});

it('allows visit link only outside create operation', function () {
    $field = SlugField::make('slug')
        ->urlHost('https://wyachts.test')
        ->urlPath('/blog/')
        ->recordSlug('existing-slug');

    expect($field->canVisitLink())->toBeFalse()
        ->and($field->getAlpineConfiguration()['visitUrl'])->toBeNull()
        ->and($field->getAlpineConfiguration()['canVisitLink'])->toBeFalse();
});

it('computes initial auto sync disabled state for edit records', function () {
    $field = SlugField::make('slug')
        ->preserveSlugOnEdit()
        ->recordSlug('existing-slug');

    expect($field->getInitialAutoSyncDisabled())->toBeTrue();

    $field = SlugField::make('slug')
        ->preserveSlugOnEdit(false)
        ->recordSlug('existing-slug');

    expect($field->getInitialAutoSyncDisabled())->toBeFalse();

    $field = SlugField::make('slug')
        ->preserveSlugOnEdit()
        ->recordSlug(null);

    expect($field->getInitialAutoSyncDisabled())->toBeFalse();
});

it('supports icon-only secondary action buttons and auto-update disabled path', function () {
    $field = SlugField::make('data.slug')
        ->source('title')
        ->actionButtonsIconOnly()
        ->autoUpdateDisabledField('slug_auto_update_disabled');

    $config = $field->getAlpineConfiguration();

    expect($config['showActionButtonLabels'])->toBeFalse()
        ->and($config['autoUpdateDisabledPath'])->toBe('data.slug_auto_update_disabled');
});

it('generates slugs via slug generator support class', function () {
    expect(SlugGenerator::fromString('Żółć & Friends', '-', 12))->toBe('zolc-friends')
        ->and(SlugGenerator::normalize('Hello--World', '-'))->toBe('hello-world')
        ->and(SlugGenerator::isHomepage('/'))->toBeTrue();
});

it('works without spatie integration configured', function () {
    $field = SlugField::make('slug')
        ->source('title')
        ->urlHost('https://example.com')
        ->urlPath('/blog/')
        ->slugSeparator('_');

    expect($field->usesSpatieIntegration())->toBeFalse()
        ->and($field->generateSlugFromSource('Hello World'))->toBe('hello_world')
        ->and($field->getFullPermalinkUrl('hello_world'))->toBe('https://example.com/blog/hello_world')
        ->and($field->getRegex())->toBe(SlugGenerator::patternForSeparator('_'))
        ->and(preg_match($field->getRegex(), 'hello_world'))->toBe(1)
        ->and(preg_match($field->getRegex(), 'hello-world'))->toBe(0);

    $config = $field->getAlpineConfiguration();

    expect($config['selfHealingPermalink'])->toBeFalse()
        ->and($config['selfHealingSeparator'])->toBe('_')
        ->and($config['separator'])->toBe('_')
        ->and($config['permalinkPreview'])->toBeTrue();
});

it('keeps custom slug pattern when explicitly configured', function () {
    $field = SlugField::make('slug')
        ->slugSeparator('_')
        ->slugPattern('/^custom$/');

    expect($field->getRegex())->toBe('/^custom$/');
});

it('derives homepage-aware slug pattern from separator by default', function () {
    $field = SlugField::make('slug')
        ->slugSeparator('_')
        ->allowHomepageSlug();

    expect($field->getRegex())->toBe(SlugGenerator::patternForSeparator('_', true))
        ->and(preg_match($field->getRegex(), '/'))->toBe(1)
        ->and(preg_match($field->getRegex(), 'hello_world'))->toBe(1);
});

it('enables server-side slug preview for translatable titles', function () {
    $field = SlugField::make('slug')
        ->translatableTitle(true)
        ->titleLocales(['pl' => 'PL', 'en' => 'EN'])
        ->slugSourceLocale('pl')
        ->source('title.pl');

    expect($field->usesTranslatableTitle())->toBeTrue()
        ->and($field->shouldUseServerSideGeneration())->toBeTrue()
        ->and($field->getAlpineConfiguration()['serverGenerate'])->toBeTrue()
        ->and($field->getAlpineConfiguration()['slugSourceLocale'])->toBe('pl');
});

it('falls back when spatie sluggable is unavailable', function (): void {
    if (SpatieSlugIntegration::isAvailable()) {
        $model = new class extends Model {};

        expect(SpatieSlugIntegration::generate('My Post Title', $model))->toBe('my-post-title');

        return;
    }

    expect(SpatieSlugIntegration::isAvailable())->toBeFalse();

    $model = new class extends Model {};

    expect(SpatieSlugIntegration::generate('My Post Title', $model))->toBe('my-post-title');
});

it('registers slug as a custom flex field component', function () {
    expect(FieldType::Slug->isCustomComponent())->toBeTrue();
});

it('builds slug field from flex field definition config', function () {
    $builder = new FlexFieldFormBuilder;

    $definition = new FlexFieldDefinition(
        slug: 'permalink',
        label: 'Permalink',
        type: FieldType::Slug,
        config: [
            'source' => 'title',
            'url_host' => 'https://example.com',
            'url_path' => '/posts/',
            'debounce' => 300,
            'slug_unique' => false,
        ],
    );

    $component = $builder->makeComponent($definition, '');

    expect($component)->toBeInstanceOf(SlugField::class)
        ->and($component->getSourceStatePath())->toBe('title')
        ->and($component->getUrlHost())->toBe('https://example.com')
        ->and($component->getUrlPath())->toBe('/posts/')
        ->and($component->getDebounceMilliseconds())->toBe(300)
        ->and($component->shouldEnforceSlugUnique())->toBeFalse();
});

it('registers slug field playground variants', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state)->toHaveKeys([
        'slug__one_liner_title',
        'slug__one_liner_slug',
        'slug__title',
        'slug__standalone',
        'slug__pair_title',
        'slug__pair_slug',
        'slug__permalink',
        'slug__sandwich',
        'slug__homepage',
    ]);
});

it('exposes slug field wrapper classes', function () {
    $field = SlugField::make('slug')->size('sm')->variant('secondary');

    expect($field->getWrapperClasses())->toBe([
        'fff-slug-field-field',
        'fff-flex-text-input-field',
        'fff-slug-field-field--sm',
        'fff-flex-text-input-field--sm',
        'fff-slug-field-field--secondary',
    ]);
});

it('applies slug unique validation by default', function () {
    $field = SlugField::make('slug');

    expect($field->shouldEnforceSlugUnique())->toBeTrue()
        ->and($field->shouldValidateSlugUniquenessLive())->toBeTrue()
        ->and($field->getSlugUniqueValidationMessage())
        ->toBe(__('filament-flex-fields::default.validation.slug.unique'));
});

it('exposes scoped slug unique configuration', function () {
    $scope = fn ($query) => $query->where('tenant_id', 1);

    $field = SlugField::make('slug')
        ->slugUniqueModel('App\\Models\\Post')
        ->slugUniqueScope($scope);

    expect($field->getSlugUniqueModel())->toBe('App\\Models\\Post')
        ->and($field->getSlugUniqueScope())->toBe($scope);
});

it('exposes slug rules api', function () {
    $field = SlugField::make('slug')
        ->slugRules(['required', 'string', 'max:120']);

    expect($field->isRequired())->toBeTrue();
});

it('exposes withTitle convenience schema', function () {
    $group = SlugField::withTitle(titleAutofocus: true);

    expect($group)->toBeInstanceOf(FusedGroup::class);
});

it('fires slug after state updated callback when slug changes', function (): void {
    $lastSlug = null;

    TestableSlugForm::$formSchema = [
        TitleSlugField::make(
            slugAfterStateUpdated: function (mixed $state) use (&$lastSlug): void {
                $lastSlug = $state;
            },
        ),
    ];

    Livewire::test(TestableSlugForm::class)
        ->set('data.slug', 'my-custom-slug');

    expect($lastSlug)->toBe('my-custom-slug');
});

it('builds title slug field with config defaults', function () {
    config()->set('filament-flex-fields.slug.field_title', 'name');
    config()->set('filament-flex-fields.slug.field_slug', 'permalink');

    $group = TitleSlugField::make();

    expect($group)->toBeInstanceOf(FusedGroup::class);
});

it('resolves spatie model class from explicit configuration', function () {
    $field = SlugField::make('slug')->spatieModel('App\\Models\\Post');

    expect($field->getSpatieModelClass())->toBe('App\\Models\\Post');
});

it('exposes server side slug generation toggle', function () {
    $field = SlugField::make('slug')->serverSideGeneration();

    expect($field->shouldUseServerSideGeneration())->toBeTrue();
});

it('translates slug ui strings in polish', function () {
    app()->setLocale('pl');

    expect(__('filament-flex-fields::default.slug.edit'))->toBe('Edytuj')
        ->and(__('filament-flex-fields::default.validation.slug.pattern'))
        ->toBe('Slug moze zawierac tylko male litery, cyfry i myslniki.');
});

it('keeps slug inline editor stretched to the full field width', function () {
    $sourceCss = file_get_contents(__DIR__.'/../../resources/css/components/slug-field.css');
    $builtCss = file_get_contents(__DIR__.'/../../resources/dist/css/slug-field.css');

    expect($sourceCss)
        ->toMatch('/\.fff-slug-field__control\s*\{[^}]*width:\s*100%/s')
        ->toMatch('/\.fff-slug-field__permalink-body\s*\{[^}]*flex:\s*1 1 0/s')
        ->toMatch('/\.fff-slug-field__permalink-body > \.fff-slug-field__editor/s')
        ->toMatch('/\.fff-slug-field__editor \.fff-slug-field__input\s*\{[^}]*flex:\s*1 1 0/s')
        ->toMatch('/\.fff-slug-field__prefix\s*\{[^}]*flex:\s*0 1 auto/s')
        ->toMatch('/\.fff-slug-field__value\s*\{[^}]*flex:\s*1 1 auto/s')
        ->and($sourceCss)->not->toMatch('/\.fff-slug-field__editor\s*\{[^}]*display:\s*grid/s');

    expect($builtCss)
        ->toMatch('/\.fff-slug-field__control\{[^}]*width:100%/s')
        ->and($builtCss)->toMatch('/\.fff-slug-field__permalink-body\{[^}]*flex:1 1 0/s')
        ->and($builtCss)->toMatch('/\.fff-slug-field__permalink-body>\.fff-slug-field__editor/s')
        ->and($builtCss)->toMatch('/\.fff-slug-field__prefix\{[^}]*flex:0(?:\s+1)?\s+auto/s');
});

it('renders inline slug editor hidden before alpine hydration', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/slug-field.blade.php');

    expect($blade)
        ->toContain('class="fff-slug-field__permalink-body"')
        ->and($blade)->toContain('class="fff-slug-field__host"')
        ->and($blade)->toMatch('/class="fff-slug-field__editor"\s+style="display: none;"\s+x-cloak\s+x-show="showInlineEditor\(\) && mode === \'edit\'"/s');
});

it('renders only one permalink preview before hydration in readonly modes', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/slug-field.blade.php');
    $css = file_get_contents(__DIR__.'/../../resources/css/components/slug-field.css');

    expect($blade)
        ->toContain('$showInitialReadonlyPreview = $slugReadOnly')
        ->and($blade)->toContain('$showInitialPreview = ! $slugReadOnly && ($inlineEditing || (! $inlineEditing && $showPermalinkChrome))')
        ->and($blade)->toContain('@unless ($showInitialReadonlyPreview) style="display: none;" x-cloak @endunless');

    expect($css)
        ->toContain('.fff-slug-field:not(.is-hydrated).is-slug-readonly .fff-slug-field__permalink-body > .fff-slug-field__preview:not(.is-readonly)')
        ->and($css)->toContain('.fff-slug-field:not(.is-hydrated):not(.is-slug-readonly) .fff-slug-field__permalink-body > .fff-slug-field__preview.is-readonly');
});

it('collapses action button labels on narrow containers via css container queries', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/slug-field.blade.php');
    $sourceCss = file_get_contents(__DIR__.'/../../resources/css/components/slug-field.css');
    $builtCss = file_get_contents(__DIR__.'/../../resources/dist/css/slug-field.css');

    expect($blade)
        ->toContain('class="fff-slug-field__button-label"')
        ->and($blade)->toContain('aria-label="{{ $labels[\'edit\'] }}"')
        ->and($blade)->toContain('title="{{ $labels[\'edit\'] }}"');

    expect($sourceCss)
        ->toContain('container-type: inline-size')
        ->and($sourceCss)->toContain('container-name: slug-field')
        ->and($sourceCss)->toMatch('/@container slug-field \(max-width: 30rem\)/')
        ->and($sourceCss)->toContain('.fff-slug-field__button-label')
        ->and($sourceCss)->toContain('.fff-slug-field__button:not(.fff-slug-field__button--icon-only)');

    expect($builtCss)
        ->toMatch('/@container slug-field \(max-width:\s*30rem\)/');
});

it('hides the secondary actions group when no secondary buttons are visible', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/slug-field.blade.php');
    $js = file_get_contents(__DIR__.'/../../resources/js/components/slug-field.js');

    expect($blade)
        ->toContain('x-show="hasSecondaryActions()"')
        ->and($blade)->toContain('@if ($hasSecondaryActionsConfig)');

    expect($js)
        ->toContain('hasSecondaryActions()')
        ->and($js)->toMatch('/hasSecondaryActions\(\)\s*\{[^}]*showRegenerateButton && this\.canRegenerate\(\)/s')
        ->and($js)->toMatch('/showCopyButton && this\.fullUrl\(\)/s')
        ->and($js)->toMatch('/showVisitLink && this\.canVisitLink && this\.fullUrl\(\)/s');
});
