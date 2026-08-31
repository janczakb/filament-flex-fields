<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableIconPickerForm;
use Livewire\Livewire;

beforeEach(function (): void {
    TestableIconPickerForm::$formSchema = [];
});

it('renders icon picker field shell and alpine configuration', function (): void {
    TestableIconPickerForm::$formSchema = [
        IconPickerField::make('icon')
            ->sets(['heroicons'])
            ->required(),
    ];

    $html = Livewire::test(TestableIconPickerForm::class)->html(false);

    expect($html)
        ->toContain('fff-icon-picker')
        ->toContain('iconPickerFieldFormComponent({')
        ->toContain('componentKey:')
        ->toContain('fi-select-input-btn')
        ->toContain('fi-select-input-value-remove-btn')
        ->toContain('fff-select-field')
        ->toContain('fi-color-primary')
        ->toContain('fff-teleported-menu__search')
        ->toContain('fi-select-input-search-ctn')
        ->toContain('fff-select-dropdown-panel--below')
        ->toContain('fff-select-dropdown-panel--dropdown-fixed')
        ->toContain('x-teleport="body"');
});

it('server renders icon picker trigger before alpine hydrates', function (): void {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/icon-picker-field.blade.php');

    expect($blade)
        ->toContain('$hasInitialSelection = filled($initialState)')
        ->toContain('icon-picker-trigger-ssr')
        ->toContain('fff-icon-picker-shell')
        ->toContain(':wrap-slot="false"')
        ->toContain(':eager="$hasInitialSelection"')
        ->toContain(':mount-immediately="$isDisabled || $isReadOnly || $hasInitialSelection"')
        ->toContain('is-trigger-hydrated')
        ->toContain("getAlpineComponentSrc('icon-picker-field'")
        ->toContain('modulepreload');
});

it('keeps wire:ignore around lazy alpine mount so clear then open cannot remorph the shell', function (): void {
    // Regression (#36): deferred clear + first search remorph used to flip
    // eager→lazy outside the old inner wire:ignore, destroying Alpine mid-open.
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/icon-picker-field.blade.php');

    $ignorePos = strpos($blade, 'wire:ignore');
    $mountPos = strpos($blade, 'lazy-alpine-mount');
    $xDataPos = strpos($blade, 'iconPickerFieldFormComponent({');

    expect($ignorePos)->not->toBeFalse()
        ->and($mountPos)->not->toBeFalse()
        ->and($xDataPos)->not->toBeFalse()
        ->and($ignorePos)->toBeLessThan($mountPos)
        ->and($mountPos)->toBeLessThan($xDataPos)
        ->and($blade)->toContain('panel appears to open then instantly close (#36)');
});

it('renders selected icon svg in trigger html when form has a value', function (): void {
    TestableIconPickerForm::$formSchema = [
        IconPickerField::make('icon')
            ->sets(['heroicons'])
            ->default('heroicon-o-star'),
    ];

    $html = Livewire::test(TestableIconPickerForm::class)
        ->fillForm(['icon' => 'heroicon-o-star'])
        ->html(false);

    expect($html)
        ->toContain('fff-icon-picker-trigger-ssr')
        ->toContain('heroicon-o-star')
        ->toContain('<svg');
});

it('returns paginated icon search results through livewire', function (): void {
    TestableIconPickerForm::$formSchema = [
        IconPickerField::make('icon')
            ->sets(['heroicons'])
            ->perPage(10),
    ];

    $livewire = Livewire::test(TestableIconPickerForm::class);
    $componentKey = $livewire->instance()->getSchema('form')->getComponent('icon')->getKey();

    $results = $livewire->instance()->callSchemaComponentMethod(
        $componentKey,
        'getIconPickerSearchResults',
        ['query' => 'star', 'set' => 'heroicons', 'page' => 1],
    );

    expect($results)
        ->toBeArray()
        ->and(collect($results['icons'])->pluck('name'))->toContain('heroicon-o-star')
        ->and($results['perPage'])->toBe(10)
        ->and($results['sets'])->toBeEmpty()
        ->and($results['previews'])->toBeArray()
        ->and(collect($results['previews'])->pluck('name'))->toContain('heroicon-o-star')
        ->and($results['previews'][0]['html'])->toContain('<svg');

    $initial = $livewire->instance()->callSchemaComponentMethod(
        $componentKey,
        'getIconPickerSearchResults',
        ['query' => '', 'set' => null, 'page' => 1],
    );

    expect($initial['sets'])->not->toBeEmpty();
});

it('does not bundle svg previews for list layout search results', function (): void {
    TestableIconPickerForm::$formSchema = [
        IconPickerField::make('icon')
            ->sets(['heroicons'])
            ->list(),
    ];

    $livewire = Livewire::test(TestableIconPickerForm::class);
    $componentKey = $livewire->instance()->getSchema('form')->getComponent('icon')->getKey();

    $results = $livewire->instance()->callSchemaComponentMethod(
        $componentKey,
        'getIconPickerSearchResults',
        ['query' => 'star', 'set' => 'heroicons', 'page' => 1],
    );

    expect($results)->not->toHaveKey('previews');
});

it('keeps icon picker results scrollable when select grid dropdown styles are loaded', function (): void {
    $iconPickerCss = file_get_contents(__DIR__.'/../../resources/css/components/icon-picker-field.css');
    $iconPickerBrowser = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/icon-picker-browser.blade.php');
    $headlessOverlaySearch = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/headless-overlay-search.blade.php');
    $selectCss = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');

    expect($iconPickerCss)
        ->toContain('.fff-icon-picker__results')
        ->toContain('overflow: auto !important')
        ->toContain('padding-block: 0.625rem 0.125rem !important')
        ->toContain('calc(100% - 0.25rem) !important')
        ->toContain('has-set-tabs')
        ->toContain('fff-icon-picker__option--loading')
        ->toContain('fff-icon-picker__initial-skeleton')
        ->toContain('fff-icon-picker__load-more-tail')
        ->toContain('overflow-anchor: none')
        ->toContain('.fff-icon-picker__option-icon-skeleton')
        ->toContain('position: absolute')
        ->toContain('inset: 0')
        ->and($iconPickerBrowser)
        ->toContain('iconLoadMoreSentinel')
        ->toContain('showLoadMoreTailSkeleton')
        ->toContain('showScrollLoadSkeleton')
        ->toContain('loadedIconItems.length > 0 && ! showInitialSkeleton && resultsGeometryReady')
        ->toContain('virtual-spacer-top')
        ->toContain('initialSkeletonSlots')
        ->and($headlessOverlaySearch)
        ->toContain('{{ $optionIdPrefix }} + {{ $activeIndexKey }}')
        ->not->toContain('\'{{ $optionIdPrefix }}-\'')
        ->and($selectCss)
        ->toContain('.fff-select-headless-menu.fff-teleported-menu:not(.is-positioned)')
        ->not->toMatch('/\.fff-select-dropdown-panel--layout-grid \.fi-select-input-options-ctn\s*\{[^}]*overflow:\s*hidden/s');
});

it('returns rendered svg previews through livewire', function (): void {
    TestableIconPickerForm::$formSchema = [
        IconPickerField::make('icon')->sets(['heroicons']),
    ];

    $livewire = Livewire::test(TestableIconPickerForm::class);
    $componentKey = $livewire->instance()->getSchema('form')->getComponent('icon')->getKey();

    $previews = $livewire->instance()->callSchemaComponentMethod(
        $componentKey,
        'getIconPickerSvgPreviews',
        ['icons' => ['heroicon-o-star']],
    );

    expect($previews)->toHaveCount(1)
        ->and($previews[0]['html'])->toContain('<svg');
});
