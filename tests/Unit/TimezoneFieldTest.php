<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Timezones;

it('exposes timezone field configuration api', function () {
    $field = TimezoneField::make('timezone')
        ->size('lg')
        ->defaultTimezone('Europe/Warsaw')
        ->timezones(['Europe/Warsaw', 'UTC', 'America/New_York'])
        ->exceptTimezones(['Etc/GMT+1'])
        ->searchable(false)
        ->showOffset(false)
        ->browserTimezoneDefault()
        ->browserTimezoneSortFirst()
        ->locale('pl')
        ->prefixIcon(GravityIcon::Clock);

    expect($field->getSize())->toBe('lg')
        ->and($field->getDefaultTimezoneIdentifier())->toBe('Europe/Warsaw')
        ->and($field->getAllowedTimezoneIdentifiers())->toBe(['Europe/Warsaw', 'UTC', 'America/New_York'])
        ->and($field->getExceptTimezoneIdentifiers())->toBe(['Etc/GMT+1'])
        ->and($field->isSearchable())->toBeFalse()
        ->and($field->shouldShowOffset())->toBeFalse()
        ->and($field->shouldUseBrowserTimezoneDefault())->toBeTrue()
        ->and($field->shouldSortTimezonesByBrowserTimezone())->toBeTrue()
        ->and($field->getLocale())->toBe('pl')
        ->and($field->getPrefixIcon())->toBe(GravityIcon::Clock);
});

it('defaults to gravity ui clock prefix icon', function () {
    expect(TimezoneField::make('timezone')->getPrefixIcon())->toBe(GravityIcon::Clock);
});

it('normalizes timezone state to iana identifier', function () {
    $field = TimezoneField::make('timezone')->defaultTimezone('Europe/Warsaw');

    expect($field->normalizeState('UTC'))->toBe('UTC')
        ->and($field->normalizeState(null))->toBeNull()
        ->and($field->normalizeState(''))->toBeNull();
});

it('dehydrates only the iana identifier never the display label', function () {
    $field = TimezoneField::make('timezone')
        ->timezones(['Europe/Warsaw', 'UTC']);

    $options = collect($field->getOptionsForJs())->keyBy('id');

    expect($options['Europe/Warsaw']['id'])->toBe('Europe/Warsaw')
        ->and($options['Europe/Warsaw']['label'])->not->toBe('Europe/Warsaw')
        ->and($options['Europe/Warsaw']['label'])->not->toContain('Poland Time')
        ->and($field->normalizeState('Europe/Warsaw'))->toBe('Europe/Warsaw')
        ->and($field->normalizeState($options['Europe/Warsaw']['label']))->toBeNull()
        ->and($field->normalizeState('Warsaw'))->toBeNull()
        ->and($field->normalizeState('Poland Time'))->toBeNull();
});

it('localizes city labels independently of the app locale', function () {
    if (! extension_loaded('intl')) {
        expect(true)->toBeTrue();

        return;
    }

    app()->setLocale('en');

    $english = TimezoneField::make('timezone')
        ->timezones(['Europe/Warsaw'])
        ->locale('en');
    $polish = TimezoneField::make('timezone')
        ->timezones(['Europe/Warsaw'])
        ->locale('pl');

    $englishOptions = collect($english->getOptionsForJs())->keyBy('id');
    $polishOptions = collect($polish->getOptionsForJs())->keyBy('id');

    expect($english->getLocale())->toBe('en')
        ->and($polish->getLocale())->toBe('pl')
        ->and($englishOptions['Europe/Warsaw']['label'])->toBe('Warsaw, Poland')
        ->and($polishOptions['Europe/Warsaw']['label'])->toBe('Warszawa, Polska')
        ->and($englishOptions['Europe/Warsaw']['id'])->toBe($polishOptions['Europe/Warsaw']['id']);
});

it('defaults to full iana timezone list except excluded ones', function () {
    $field = TimezoneField::make('timezone')->exceptTimezones(['America/Adak']);

    $ids = collect($field->getTimezonesMetadata())->pluck('id')->all();

    expect($ids)->not->toContain('America/Adak')
        ->and($ids)->toContain('Europe/Warsaw', 'UTC')
        ->and(count($ids))->toBe(count(Timezones::allIdentifiers()) - 1);
});

it('does not use laravel required rule on nullable state', function () {
    $field = TimezoneField::make('timezone')->required();

    expect($field->getRequiredValidationRule())->toBe('nullable')
        ->and($field->getValidationRules())->not->toContain('required');
});

it('requires a timezone when the field is required', function () {
    $field = TimezoneField::make('timezone')->required()->label('Timezone');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('timezone', null, function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('validation.required', ['attribute' => 'Timezone']));
});

it('rejects timezones outside the allowed list', function () {
    $field = TimezoneField::make('timezone')
        ->timezones(['Europe/Warsaw', 'UTC'])
        ->label('Timezone');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('timezone', 'America/New_York', function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('validation.in', ['attribute' => 'Timezone']));
});

it('registers timezone field playground variants', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state)->toHaveKeys([
        'timezone__basic',
        'timezone__limited',
        'timezone__browser',
    ]);
});

it('includes wrapper classes for size and variant', function () {
    $field = TimezoneField::make('timezone')
        ->size('sm')
        ->variant('secondary');

    expect($field->getWrapperClasses())->toBe([
        'fff-timezone-field',
        'fff-flex-text-input-field',
        'fff-timezone-field--sm',
        'fff-rounding-md',
        'fff-flex-text-input-field--sm',
        'fff-rounding-md',
        'fff-timezone-field--secondary',
        'fff-flex-text-input-field--secondary',
    ]);
});

it('exposes focus outline api', function () {
    expect(TimezoneField::make('timezone')->shouldShowFocusOutline())->toBeFalse()
        ->and(TimezoneField::make('timezone')->focusOutline()->shouldShowFocusOutline())->toBeTrue();
});

it('rejects unsupported timezone field variants', function () {
    TimezoneField::make('timezone')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('inlines blocking browser timezone ssr boot for empty browserTimezoneDefault fields', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/timezone-browser-ssr-boot.blade.php');

    expect($blade)
        ->toContain('timezoneBrowserSsrInlineContents()')
        ->toContain('data-fff-timezone-boot')
        ->toContain('getBrowserTimezoneBootCatalog()')
        ->not->toContain('getScriptSrc');
});

it('builds a compact catalog matching alpine timezone labels', function () {
    $field = TimezoneField::make('timezone')
        ->timezones(['Europe/Warsaw', 'UTC']);

    $catalog = $field->getBrowserTimezoneBootCatalog();
    $options = collect($field->getOptionsForJs())->keyBy('id');

    expect($catalog['Europe/Warsaw'][0])->toBe($options['Europe/Warsaw']['label'])
        ->and($catalog['Europe/Warsaw'][1])->toBe($options['Europe/Warsaw']['offset'])
        ->and($catalog['UTC'][0])->toBe($options['UTC']['label']);
});

it('includes utc in the default iana list and can hide it with includeUtc', function () {
    $withUtc = TimezoneField::make('timezone');
    $withoutUtc = TimezoneField::make('timezone')->includeUtc(false);

    expect($withUtc->shouldIncludeUtc())->toBeTrue()
        ->and($withoutUtc->shouldIncludeUtc())->toBeFalse()
        ->and($withUtc->getExceptTimezoneIdentifiers())->not->toContain('UTC')
        ->and($withoutUtc->getExceptTimezoneIdentifiers())->toContain('UTC')
        ->and(collect($withUtc->getTimezonesMetadata())->pluck('id')->all())->toContain('UTC')
        ->and(collect($withoutUtc->getTimezonesMetadata())->pluck('id')->all())->not->toContain('UTC');
});

it('does not duplicate utc when includeUtc is false and utc is already excepted', function () {
    $field = TimezoneField::make('timezone')
        ->includeUtc(false)
        ->exceptTimezones(['UTC']);

    expect($field->getExceptTimezoneIdentifiers())->toBe(['UTC']);
});
