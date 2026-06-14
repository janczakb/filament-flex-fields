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

it('keeps flex text input shell styles separate from date time field bundle', function () {
    $dateTimeCss = file_get_contents(__DIR__.'/../../resources/dist/css/flex-date-time-field.css');
    $flexTextInputCss = file_get_contents(__DIR__.'/../../resources/dist/css/flex-text-input.css');

    expect($dateTimeCss)
        ->toContain('.fff-date-time-field .fff-flex-text-input__shell')
        ->not->toContain('.fff-flex-text-input--md');

    expect($flexTextInputCss)->toContain('.fff-flex-text-input__shell');
});

it('uses the stylesheet queue partial for css and alpine chunk preloads', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/load-stylesheet.blade.php');

    expect($blade)
        ->toContain('FlexFieldStylesheetQueue::enqueueFor')
        ->toContain('FlexFieldAlpineQueue::enqueueChunksFor')
        ->toContain('modulepreload')
        ->toContain('alpineChunkSrc');
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
        ->not->toContain('.fff-map-picker__dropdown-hint')
        ->toContain('.fff-address-autocomplete__search-wrap .fff-map-picker__dropdown-panel');
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

it('preloads hold confirm alpine module on panel pages', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/hold-confirm-action-preload.blade.php');

    expect($blade)
        ->toContain('modulepreload')
        ->toContain('hold-confirm-action');
});

it('preloads deduplicated component stylesheets on playground pages', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/pages/flex-fields-playground-component.blade.php');

    expect($blade)
        ->toContain('FlexFieldStylesheetQueue::enqueueFor')
        ->toContain('shouldLoadStylesheetsFor')
        ->toContain('resolveStylesheetComponent');
});
