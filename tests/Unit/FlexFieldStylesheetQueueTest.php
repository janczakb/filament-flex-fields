<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;

beforeEach(function () {
    FlexFieldStylesheetQueue::reset();
});

it('deduplicates stylesheet links across multiple fields on one request', function () {
    expect(FlexFieldStylesheetQueue::enqueueFor('phone-field'))
        ->toBe(['flex-text-input', 'phone-field'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('country-field'))
        ->toBe(['country-field'])
        ->and(FlexFieldStylesheetQueue::has('flex-text-input'))->toBeTrue()
        ->and(FlexFieldStylesheetQueue::has('phone-field'))->toBeTrue()
        ->and(FlexFieldStylesheetQueue::has('country-field'))->toBeTrue();
});

it('deduplicates repeated enqueue calls for the same component', function () {
    expect(FlexFieldStylesheetQueue::enqueueFor('phone-field'))
        ->toBe(['flex-text-input', 'phone-field'])
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
        ->toBe(['flex-text-input', 'phone-field']);
});

it('loads flex text input styles before date time field stylesheet', function () {
    expect(FlexFieldAssets::stylesheetsFor('flex-date-time-field'))
        ->toBe(['flex-text-input', 'flex-date-time-field'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('flex-date-time-field'))
        ->toBe(['flex-text-input', 'flex-date-time-field']);
});

it('resolves playground slug aliases to lazy stylesheet component ids', function () {
    expect(FlexFieldAssets::resolveStylesheetComponent('date-time-fields'))
        ->toBe('flex-date-time-field')
        ->and(FlexFieldAssets::shouldLoadStylesheetsFor('date-time-fields'))->toBeTrue()
        ->and(FlexFieldStylesheetQueue::enqueueFor('date-time-fields'))
        ->toBe(['flex-text-input', 'flex-date-time-field']);
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

    expect($countryCss)
        ->toContain('--fff-phone-field-menu-text')
        ->toContain('.fff-country-field__option')
        ->toMatch('/\.dark\s+\.fff-country-field__menu[\s\S]*--fff-phone-field-menu-text:#fafafa/');
});

it('keeps flex text input shell styles separate from date time field bundle', function () {
    $dateTimeCss = file_get_contents(__DIR__.'/../../resources/dist/css/flex-date-time-field.css');
    $flexTextInputCss = file_get_contents(__DIR__.'/../../resources/dist/css/flex-text-input.css');

    expect($dateTimeCss)
        ->toContain('.fff-date-time-field .fff-flex-text-input__shell')
        ->not->toContain('.fff-flex-text-input--md');

    expect($flexTextInputCss)->toContain('.fff-flex-text-input__shell');
});

it('pushes lazy stylesheets and alpine chunk preloads to the head styles stack', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/load-stylesheet.blade.php');

    expect($blade)
        ->toContain('@pushOnce')
        ->toContain('bjanczak-flex-fields:stylesheet:')
        ->toContain('FlexFieldStylesheetQueue::enqueueFor')
        ->toContain('FlexFieldAlpineQueue::enqueueChunksFor')
        ->toContain('rel="stylesheet"')
        ->toContain('modulepreload')
        ->toContain('data-navigate-track')
        ->toContain('alpineChunkSrc');
});

it('registers navigate dedupe script for flex fields lazy assets', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/lazy-assets-navigate-dedupe.blade.php');

    expect($blade)
        ->toContain('livewire:navigated')
        ->toContain('filament-flex-fields');
});

it('loads flex text input and map picker dropdown before address autocomplete stylesheet', function () {
    expect(FlexFieldAssets::stylesheetsFor('address-autocomplete'))
        ->toBe(['flex-text-input', 'map-picker-dropdown', 'address-autocomplete'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('address-autocomplete'))
        ->toBe(['flex-text-input', 'map-picker-dropdown', 'address-autocomplete']);
});

it('loads map picker dropdown before map picker stylesheet', function () {
    expect(FlexFieldAssets::stylesheetsFor('map-picker'))
        ->toBe(['map-picker-dropdown', 'map-picker'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('map-picker'))
        ->toBe(['map-picker-dropdown', 'map-picker']);
});

it('loads select field, tag chips, and user display stylesheets before user select stylesheet', function () {
    expect(FlexFieldAssets::stylesheetsFor('user-select'))
        ->toBe(['select-field', 'tag-chips', 'user-display', 'user-select'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('user-select'))
        ->toBe(['select-field', 'tag-chips', 'user-display', 'user-select']);
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
        ->toBe(['flex-text-input', 'tag-chips', 'tags-field'])
        ->and(FlexFieldStylesheetQueue::enqueueFor('tags-field'))
        ->toBe(['flex-text-input', 'tag-chips', 'tags-field']);
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

    expect($dropdownCss)
        ->toContain('.fff-map-picker__dropdown-panel')
        ->toContain('.fff-map-picker__dropdown-hint');

    expect($mapPickerCss)
        ->not->toContain('.fff-map-picker__dropdown-hint');

    expect($addressAutocompleteCss)
        ->not->toContain('.fff-map-picker__dropdown-hint');
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

it('preloads hold confirm alpine module on panel pages through the head styles stack', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/hold-confirm-action-preload.blade.php');

    expect($blade)
        ->toContain("@push('styles')")
        ->toContain('modulepreload')
        ->toContain('hold-confirm-action')
        ->toContain('data-navigate-track');
});

it('renders queued playground component stylesheets in the page push block', function () {
    $stylesPartial = file_get_contents(__DIR__.'/../../resources/views/partials/playground-page-stylesheets.blade.php');

    expect($stylesPartial)
        ->toContain('playgroundStylesheetHrefForRequest()')
        ->toContain('data-fff-playground-bundle')
        ->toContain('data-navigate-track');
});
