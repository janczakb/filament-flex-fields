<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldAlpineQueue;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Illuminate\Http\Request;

beforeEach(function () {
    FlexFieldStylesheetQueue::reset();
    FlexFieldAlpineQueue::reset();
});

it('deduplicates stylesheet links across multiple fields on one request', function () {
    expect(FlexFieldStylesheetQueue::enqueueFor('phone-field'))
        ->toBe(['emoji-picker', 'flex-text-input', 'teleported-menu', 'phone-field'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('country-field'))
        ->toBe(['country-field'])
        ->and(FlexFieldStylesheetQueue::has('flex-text-input'))->toBeTrue()
        ->and(FlexFieldStylesheetQueue::has('teleported-menu'))->toBeTrue()
        ->and(FlexFieldStylesheetQueue::has('phone-field'))->toBeTrue()
        ->and(FlexFieldStylesheetQueue::has('country-field'))->toBeTrue();
});

it('deduplicates repeated enqueue calls for the same component', function () {
    expect(FlexFieldStylesheetQueue::enqueueFor('phone-field'))
        ->toBe(['emoji-picker', 'flex-text-input', 'teleported-menu', 'phone-field'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('phone-field'))
        ->toBe([]);
});

it('deduplicates emoji picker stylesheet across flex text input and textarea fields', function () {
    expect(FlexFieldStylesheetQueue::enqueueFor('flex-text-input'))
        ->toBe(['emoji-picker', 'flex-text-input'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('flex-textarea'))
        ->toBe(['flex-textarea']);
});

it('resolves stylesheet dependencies without duplication inside one component', function () {
    expect(FlexFieldAssets::stylesheetsFor('phone-field'))
        ->toBe(['emoji-picker', 'flex-text-input', 'teleported-menu', 'phone-field']);
});

it('loads flex text input styles before date time field stylesheet', function () {
    expect(FlexFieldAssets::stylesheetsFor('flex-date-time-field'))
        ->toBe(['emoji-picker', 'flex-text-input', 'flex-date-time-field'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('flex-date-time-field'))
        ->toBe(['emoji-picker', 'flex-text-input', 'flex-date-time-field']);
});

it('resolves playground slug aliases to lazy stylesheet component ids', function () {
    expect(FlexFieldAssets::resolveStylesheetComponent('date-time-fields'))
        ->toBe('flex-date-time-field')
        ->and(FlexFieldAssets::shouldLoadStylesheetsFor('date-time-fields'))->toBeTrue()
        ->and(FlexFieldAssets::playgroundStylesheetsFor('date-time-fields'))
        ->toBe(['emoji-picker', 'flex-text-input', 'flex-date-time-field', 'teleported-menu', 'flex-time-segments'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('date-time-fields'))
        ->toBe(['emoji-picker', 'flex-text-input', 'flex-date-time-field']);
});

it('resolves matrix-choice and rating playground slugs to field bundle ids', function () {
    expect(FlexFieldAssets::resolveStylesheetComponent('matrix-choice'))
        ->toBe('matrix-choice-field')
        ->and(FlexFieldAssets::playgroundStylesheetsFor('matrix-choice'))
        ->toContain('matrix-choice-field')
        ->and(FlexFieldAssets::resolveStylesheetComponent('rating'))
        ->toBe('rating-field')
        ->and(FlexFieldAssets::playgroundStylesheetsFor('rating'))
        ->toContain('rating-field');
});

it('keeps flex text input in its own bundle separate from phone field', function () {
    $phoneCss = file_get_contents(__DIR__.'/../../resources/dist/css/phone-field.css');
    $flexTextInputCss = file_get_contents(__DIR__.'/../../resources/dist/css/flex-text-input.css');

    expect($phoneCss)
        ->toContain('.fff-phone-field__flag-wrap')
        ->not->toContain('.fff-flex-text-input--md');

    expect($flexTextInputCss)
        ->toContain('.fff-flex-text-input--md')
        ->toContain('--fff-flex-text-input-icon-size');
});

it('scopes phone country dropdown text tokens on the teleported menu for dark mode', function () {
    $phoneCss = file_get_contents(__DIR__.'/../../resources/dist/css/phone-field.css');

    expect($phoneCss)
        ->toContain('--fff-phone-field-menu-text')
        ->toContain('--fff-phone-field-menu-muted')
        ->toContain('.fff-phone-field__country-option')
        ->toMatch('/\.dark\s+\.fff-phone-field__country-menu[\s\S]*--fff-phone-field-menu-text:#fafafa/');
});

it('scopes country field dropdown text tokens on the teleported menu for dark mode', function () {
    $countryCss = file_get_contents(__DIR__.'/../../resources/dist/css/country-field.css');
    $teleportedMenuCss = file_get_contents(__DIR__.'/../../resources/dist/css/teleported-menu.css');

    expect($countryCss)
        ->toContain('--fff-phone-field-menu-text')
        ->toContain('.fff-country-field__option');

    expect($teleportedMenuCss)
        ->toContain('.dark .fff-teleported-menu')
        ->toContain('--fff-phone-field-menu-text:#fafafa');
});

it('keeps flex text input shell styles separate from date time field bundle', function () {
    $dateTimeCss = file_get_contents(__DIR__.'/../../resources/dist/css/flex-date-time-field.css');
    $flexTextInputCss = file_get_contents(__DIR__.'/../../resources/dist/css/flex-text-input.css');

    expect($dateTimeCss)
        ->toContain('.fff-date-time-field .fff-flex-text-input__shell')
        ->not->toContain('.fff-flex-text-input--md');

    expect($flexTextInputCss)->toContain('.fff-flex-text-input__shell');
});

it('removes inactive upload source panels from layout flow', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/components/flex-file-upload.css');

    expect($css)
        ->toContain('.fff-flex-file-upload__source-panels > .fff-flex-file-upload__source-panel:not(.is-active)')
        ->toContain('position: absolute');
});

it('uses flow layout with gaps for compact file lists', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/components/flex-file-upload.css');

    expect($css)
        ->toContain('--fff-flex-file-upload-list-offset')
        ->toContain('--fff-flex-file-upload-list-gap')
        ->toContain(".filepond--root[data-style-panel-layout='compact']:has(.filepond--item)")
        ->toContain('gap: var(--fff-flex-file-upload-list-gap)')
        ->toContain('contain: none !important');
});

it('shows skeleton overlays for pending modal and inline morph targets', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/core/asset-injector.css');

    expect($css)
        ->toContain('.fff-flex-fields-assets-pending:not(.fi-modal)')
        ->toContain('.fff-flex-fields-assets-ready:not(.fi-modal)')
        ->toContain('fff-flex-fields-assets-skeleton')
        ->toContain('.fi-modal.fff-flex-fields-assets-pending > .fi-modal-window-ctn > .fi-modal-window::after')
        ->toContain('inset: 0')
        ->not->toContain('inset: 1.5rem');
});

it('emits pending assets inline when a field registers stylesheets', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/load-stylesheet.blade.php');

    expect($blade)
        ->toContain('FlexFieldStylesheetQueue::enqueueFor')
        ->toContain('FlexFieldAlpineQueue::enqueueChunksFor')
        ->toContain('emit-assets')
        ->toContain('markStylesheetsEmitted')
        ->not->toContain('@pushOnce');
});

it('emits pending assets from a single queued-stylesheets injector', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/queued-stylesheets.blade.php');

    expect($blade)
        ->toContain('FlexFieldStylesheetQueue::pending()')
        ->toContain('FlexFieldAlpineQueue::pending()')
        ->toContain('emit-assets')
        ->toContain('markStylesheetsEmitted')
        ->toContain('markChunksEmitted');
});

it('tracks emitted stylesheets separately from registration', function () {
    expect(FlexFieldStylesheetQueue::enqueueFor('phone-field'))
        ->toBe(['emoji-picker', 'flex-text-input', 'teleported-menu', 'phone-field'])
        ->and(FlexFieldStylesheetQueue::pending())
        ->toBe(['emoji-picker', 'flex-text-input', 'teleported-menu', 'phone-field']);

    FlexFieldStylesheetQueue::markStylesheetsEmitted(['emoji-picker', 'flex-text-input', 'teleported-menu', 'phone-field']);

    expect(FlexFieldStylesheetQueue::pending())->toBe([])
        ->and(FlexFieldStylesheetQueue::registered())
        ->toBe(['emoji-picker', 'flex-text-input', 'teleported-menu', 'phone-field']);
});

it('simulates the multi-field asset audit without duplicate emissions', function () {
    $log = [];

    $render = function (int $index, string $component) use (&$log): void {
        $pendingStylesheets = FlexFieldStylesheetQueue::enqueueFor($component);
        $pendingChunks = FlexFieldAlpineQueue::enqueueChunksFor($component);

        $log[] = [
            'index' => $index,
            'component' => $component,
            'stylesheets' => $pendingStylesheets,
            'chunks' => $pendingChunks,
        ];
    };

    $render(0, 'flex-text-input');
    $render(1, 'flex-text-input');
    $render(2, 'phone-field');
    $render(3, 'phone-field');
    $render(4, 'schedule-field');

    expect($log[0]['stylesheets'])->toBe(['emoji-picker', 'flex-text-input'])
        ->and($log[1]['stylesheets'])->toBe([])
        ->and($log[2]['stylesheets'])->toBe(['teleported-menu', 'phone-field'])
        ->and($log[3]['stylesheets'])->toBe([])
        ->and($log[4]['stylesheets'])->toBe(['switch', 'timezone-field', 'flex-time-segments', 'schedule-field']);

    $firstPass = view('filament-flex-fields::partials.queued-stylesheets')->render();

    expect($firstPass)
        ->toContain('data-fff-asset-batch')
        ->toContain('flex-fields-flex-text-input')
        ->toContain('flex-fields-teleported-menu')
        ->toContain('flex-fields-schedule-field');

    $secondPass = view('filament-flex-fields::partials.queued-stylesheets')->render();

    expect($secondPass)->toBe('');
});

it('registers unified asset injector script for livewire, spa, and modal flows', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/flex-field-asset-injector.blade.php');
    $module = file_get_contents(__DIR__.'/../../resources/js/core/flex-field-asset-injector.js');

    expect($blade)
        ->toContain('flex-field-asset-injector')
        ->toContain('FilamentAsset::getScriptSrc')
        ->toContain('data-navigate-track');

    expect($module)
        ->toContain('morph.updating')
        ->toContain('morph.updated')
        ->toContain('beginPendingMorph')
        ->toContain('preloadBatchesIn')
        ->toContain('rootNeedsAssetLoading')
        ->toContain('resolvePendingTarget(el)')
        ->toContain('registerInjectorHooks')
        ->toContain('pendingMorphTargets.add(target)')
        ->toContain('pendingMorphTargets.add(element)')
        ->toContain('cleanupClosedModalPendingState')
        ->toContain('modal-closed')
        ->toContain('fff-flex-fields-assets-pending')
        ->toContain('data-fff-asset-batch')
        ->toContain('normalizeAssetUrl')
        ->toContain('inflightRequests')
        ->toContain('closest(\'.fi-modal\')')
        ->toContain('data-fff-playground-bundle')
        ->toContain('data-fff-stylesheet')
        ->toContain('data-fff-alpine-chunk');
});

it('registers playground skeleton demo script separately from the core injector', function () {
    $script = file_get_contents(__DIR__.'/../../resources/views/partials/playground-skeleton-demo-script.blade.php');
    $demoModule = file_get_contents(__DIR__.'/../../resources/js/playground/skeleton-demo.js');

    expect($script)
        ->toContain('PLAYGROUND_SKELETON_DEMO_SCRIPT_ID')
        ->toContain('data-navigate-track');

    expect($demoModule)
        ->toContain('installPlaygroundSkeletonDemo')
        ->toContain('registerInjectorHooks')
        ->toContain('FffSkeletonDemo')
        ->not->toContain('morph.updating');
});

it('pushes blocking head assets on full page and inlines them for livewire requests', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/emit-assets.blade.php');

    expect($blade)
        ->toContain('Livewire::isLivewireRequest()')
        ->toContain("@push('styles')")
        ->toContain('data-fff-asset-batch')
        ->toContain('data-fff-stylesheet')
        ->toContain('data-fff-alpine-chunk');
});

it('loads flex text input and map picker dropdown before address autocomplete stylesheet', function () {
    expect(FlexFieldAssets::stylesheetsFor('address-autocomplete'))
        ->toBe(['emoji-picker', 'flex-text-input', 'teleported-menu', 'map-picker-dropdown', 'address-autocomplete'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('address-autocomplete'))
        ->toBe(['emoji-picker', 'flex-text-input', 'teleported-menu', 'map-picker-dropdown', 'address-autocomplete']);
});

it('loads map picker dropdown before map picker stylesheet', function () {
    expect(FlexFieldAssets::stylesheetsFor('map-picker'))
        ->toBe(['teleported-menu', 'map-picker-dropdown', 'map-picker'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('map-picker'))
        ->toBe(['teleported-menu', 'map-picker-dropdown', 'map-picker']);
});

it('loads select field, tag chips, and user display stylesheets before user select stylesheet', function () {
    expect(FlexFieldAssets::stylesheetsFor('user-select'))
        ->toBe(['teleported-menu', 'select-field', 'tag-chips', 'user-display', 'user-select'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('user-select'))
        ->toBe(['teleported-menu', 'select-field', 'tag-chips', 'user-display', 'user-select']);
});

it('loads user display stylesheet before user column stylesheet', function () {
    expect(FlexFieldAssets::stylesheetsFor('user-column'))
        ->toBe(['user-display', 'user-column'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('user-column'))
        ->toBe(['user-display', 'user-column']);
});

it('loads rating column stylesheet as a dedicated lazy bundle', function () {
    expect(FlexFieldAssets::stylesheetsFor('rating-column'))
        ->toBe(['rating-column'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('rating-column'))
        ->toBe(['rating-column']);
});

it('loads icon column stylesheet as a dedicated lazy bundle', function () {
    expect(FlexFieldAssets::stylesheetsFor('icon-column'))
        ->toBe(['icon-column'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('icon-column'))
        ->toBe(['icon-column']);
});

it('suppresses icon column lazy css when playground slug bundle is active', function () {
    FlexFieldStylesheetQueue::suppressForPlaygroundBundle(['icon-column']);

    expect(FlexFieldStylesheetQueue::enqueueFor('icon-column'))->toBe([]);
});

it('keeps shared user display primitives in source bundles', function () {
    $userDisplayCss = file_get_contents(__DIR__.'/../../resources/css/components/user-display.css');
    $userColumnCss = file_get_contents(__DIR__.'/../../resources/css/core/tables/user-column.css');
    $userSelectCss = file_get_contents(__DIR__.'/../../resources/css/components/user-select-inline.css');

    expect($userDisplayCss)
        ->toContain('.fff-user-select__avatar-surface')
        ->toContain('.fff-user-select-option__name');

    expect($userColumnCss)
        ->toContain('.fff-user-column__avatar-stack')
        ->not->toContain('bg-gradient-to-b from-blue-300 to-blue-500');

    expect($userSelectCss)
        ->toContain('.fff-user-select__dropdown-skeleton')
        ->not->toContain('bg-gradient-to-b from-blue-300 to-blue-500');
});

it('loads tag chips stylesheet before tags field stylesheet', function () {
    expect(FlexFieldAssets::stylesheetsFor('tags-field'))
        ->toBe(['emoji-picker', 'flex-text-input', 'tag-chips', 'tags-field'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('tags-field'))
        ->toBe(['emoji-picker', 'flex-text-input', 'tag-chips', 'tags-field']);
});

it('keeps shared tag chip styles in a separate bundle', function () {
    $tagChipsCss = file_get_contents(__DIR__.'/../../resources/dist/css/tag-chips.css');
    $tagsFieldCss = file_get_contents(__DIR__.'/../../resources/dist/css/tags-field.css');
    $userSelectCss = file_get_contents(__DIR__.'/../../resources/dist/css/user-select.css');

    expect($tagChipsCss)
        ->toContain('.fff-tags-field__tag')
        ->toContain('.fff-tags-field__tag-remove');

    expect($tagsFieldCss)
        ->toContain('.fff-tags-field__suggestion')
        ->not->toContain('.fff-tags-field__tag-remove:hover');

    expect($userSelectCss)
        ->toContain('.fff-user-select__selected-tags')
        ->not->toContain('.fff-tags-field__tag-remove:hover');
});

it('keeps select field core styles separate from the user select bundle', function () {
    $selectFieldCss = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');
    $userSelectCss = file_get_contents(__DIR__.'/../../resources/dist/css/user-select.css');

    expect($selectFieldCss)->toContain('.fff-select-field--layout-grid');
    expect($userSelectCss)
        ->toContain('.fff-user-select-option--list')
        ->not->toContain('.fff-select-field--layout-grid');
});

it('keeps shared map picker dropdown styles in a separate bundle', function () {
    $dropdownCss = file_get_contents(__DIR__.'/../../resources/dist/css/map-picker-dropdown.css');
    $mapPickerCss = file_get_contents(__DIR__.'/../../resources/dist/css/map-picker.css');
    $addressAutocompleteCss = file_get_contents(__DIR__.'/../../resources/dist/css/address-autocomplete.css');
    $teleportedMenuCss = file_get_contents(__DIR__.'/../../resources/dist/css/teleported-menu.css');

    expect($dropdownCss)
        ->toContain('.fff-map-picker__dropdown-panel')
        ->toContain('.fff-map-picker__dropdown-hint')
        ->not->toContain('#40404573');

    expect($teleportedMenuCss)->toContain('.fff-teleported-menu');

    expect($mapPickerCss)
        ->not->toContain('.fff-map-picker__dropdown-hint');

    expect($addressAutocompleteCss)
        ->not->toContain('.fff-map-picker__dropdown-hint');
});

it('keeps shared teleported menu styles in a separate bundle', function () {
    $teleportedMenuCss = file_get_contents(__DIR__.'/../../resources/dist/css/teleported-menu.css');
    $countryCss = file_get_contents(__DIR__.'/../../resources/dist/css/country-field.css');
    $timezoneCss = file_get_contents(__DIR__.'/../../resources/dist/css/timezone-field.css');
    $selectCss = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($teleportedMenuCss)
        ->toContain('.fff-teleported-menu')
        ->toContain('.fff-teleported-menu__search')
        ->toContain(':has(.fi-modal.fi-modal-open) .fff-teleported-menu')
        ->toContain('#40404573');

    expect($countryCss)
        ->toContain('.fff-country-field__menu')
        ->not->toContain('.fff-teleported-menu__search');

    expect($timezoneCss)
        ->toContain('.fff-timezone-field__menu')
        ->not->toContain('.fff-teleported-menu__search');

    expect($selectCss)
        ->toContain('.fff-select-field')
        ->not->toContain('.fff-teleported-menu__search');
});

it('resolves flex radiolist playground slug to flex checklist stylesheet', function () {
    expect(FlexFieldAssets::resolveStylesheetComponent('flex-radiolist'))
        ->toBe('flex-checklist')
        ->and(FlexFieldAssets::shouldLoadStylesheetsFor('flex-radiolist'))->toBeTrue()
        ->and(FlexFieldStylesheetQueue::enqueueFor('flex-radiolist'))
        ->toBe(['flex-checklist']);
});

it('keeps flex radiolist styles in the flex checklist bundle', function () {
    $checklistCss = file_get_contents(__DIR__.'/../../resources/dist/css/flex-checklist.css');

    expect($checklistCss)
        ->toContain('.fff-flex-radiolist')
        ->toContain('.fff-flex-radiolist__indicator-dot');
});

it('preloads hold confirm alpine module when the action renders', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/actions/hold-confirm.blade.php');

    expect($blade)
        ->toContain('modulepreload')
        ->toContain('hold-confirm-action')
        ->toContain('data-navigate-track')
        ->not->toContain('hold-confirm-action-preload');
});

it('renders queued playground component stylesheets in the page push block', function () {
    $stylesPartial = file_get_contents(__DIR__.'/../../resources/views/partials/playground-page-stylesheets.blade.php');

    expect($stylesPartial)
        ->toContain('playgroundStylesheetHrefForRequest()')
        ->toContain('suppressForPlaygroundBundle')
        ->toContain('playgroundStylesheetsFor($playgroundSlug)')
        ->toContain("@push('styles')")
        ->toContain('data-fff-playground-bundle')
        ->toContain('data-navigate-track');
});

it('does not enqueue lazy stylesheets already bundled on playground slug pages', function () {
    FlexFieldStylesheetQueue::suppressForPlaygroundBundle([
        'teleported-menu',
        'select-field',
        'icon-picker-field',
    ]);

    expect(FlexFieldStylesheetQueue::enqueueFor('icon-picker-field'))->toBe([]);
});

it('preloads teleported menu only when the stylesheet queue needs it', function () {
    app()->instance('request', Request::create('/admin/resources/posts/edit', 'GET'));

    expect(FlexFieldAssets::criticalPreloadStylesheets())->toBe([]);

    FlexFieldStylesheetQueue::enqueueFor('select-field');

    expect(FlexFieldAssets::criticalPreloadStylesheets())
        ->toBe(['teleported-menu']);
});

it('tracks teleported menu in the stylesheet queue', function () {
    expect(FlexFieldStylesheetQueue::hasQueuedTeleportedMenu())->toBeFalse();

    FlexFieldStylesheetQueue::enqueueFor('phone-field');

    expect(FlexFieldStylesheetQueue::hasQueuedTeleportedMenu())->toBeTrue();
});

it('scopes critical stylesheet preloads to the active playground slug', function () {
    app()->instance('request', Request::create('/admin/flex-fields-playground/icon-picker-field', 'GET'));

    expect(FlexFieldAssets::hasPlaygroundBundleForSlug('icon-picker-field'))->toBeTrue()
        ->and(FlexFieldAssets::criticalPreloadStylesheets())->toBe([]);
});

it('skips critical stylesheet preloads when the playground slug has a bundled stylesheet', function () {
    app()->instance('request', Request::create('/admin/flex-fields-playground/select-field', 'GET'));

    expect(FlexFieldAssets::hasPlaygroundBundleForSlug('select-field'))->toBeTrue()
        ->and(FlexFieldAssets::criticalPreloadStylesheets())->toBe([]);
});
