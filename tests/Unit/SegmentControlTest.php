<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

it('exposes segment control configuration via fluent api', function () {
    $field = SegmentControl::make('destination_type')
        ->options([
            'single' => 'Single URL',
            'split' => 'A/B Split',
        ])
        ->icons([
            'single' => GravityIcon::Link,
        ])
        ->disabledOptions(['split'])
        ->size(ControlSize::Lg)
        ->variant('ghost')
        ->separators(false)
        ->fullWidth()
        ->iconOnly()
        ->expandSelectedLabel()
        ->color('danger');

    $options = $field->getNormalizedOptions();

    expect($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('ghost')
        ->and($field->getColor())->toBe('danger')
        ->and($field->hasSeparators())->toBeFalse()
        ->and($field->isFullWidth())->toBeTrue()
        ->and($field->isIconOnly())->toBeTrue()
        ->and($field->shouldExpandSelectedLabel())->toBeTrue()
        ->and($options['single']['label'])->toBe('Single URL')
        ->and($options['single']['icon'])->toBe(GravityIcon::Link)
        ->and($options['split']['disabled'])->toBeTrue()
        ->and($field->getOptionKeys())->toBe(['single', 'split']);
});

it('accepts heroicon or any icon set in segment control options', function () {
    $field = SegmentControl::make('destination_type')
        ->icons([
            'single' => 'heroicon-o-link',
        ])
        ->options([
            'single' => 'Single URL',
            'split' => 'A/B Split',
        ]);

    expect($field->getNormalizedOptions()['single']['icon'])->toBe('heroicon-o-link');
});

it('defaults ghost variant color to primary', function () {
    $field = SegmentControl::make('billing')
        ->variant('ghost')
        ->options(['monthly' => 'Monthly', 'yearly' => 'Yearly']);

    expect($field->getColor())->toBe('primary');
});

it('normalizes rich option arrays for segment control', function () {
    $field = SegmentControl::make('theme')
        ->options([
            'light' => [
                'label' => 'Light',
                'icon' => GravityIcon::Sun,
            ],
            'dark' => [
                'label' => 'Dark',
                'icon' => GravityIcon::Moon,
                'disabled' => true,
                'tooltip' => 'Dark mode unavailable',
            ],
        ]);

    $options = $field->getNormalizedOptions();

    expect($options['dark']['disabled'])->toBeTrue()
        ->and($options['dark']['tooltip'])->toBe('Dark mode unavailable')
        ->and($options['light']['icon'])->toBe(GravityIcon::Sun);
});

it('server renders segment selected state before alpine hydrates', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/segment-control.blade.php');
    $css = file_get_contents(__DIR__.'/../../resources/css/components/segment-control.css');

    expect($blade)
        ->toContain('$currentState = $getState()')
        ->toContain('$normalizedCurrentState')
        ->toContain('$isSelected = $normalizedCurrentState !== null && (string) $value === $normalizedCurrentState')
        ->toContain('data-segment-selected="{{ $isSelected ? \'true\' : \'false\' }}"')
        ->toContain('aria-checked="{{ $isSelected ? \'true\' : \'false\' }}"')
        ->toContain('@unless ($isSelected)')
        ->toContain('x-show="isSelected(@js($value))"')
        ->toContain("'is-hydrated': indicatorHydrated");

    expect($css)
        ->toContain('.fff-segment-track:not(.is-hydrated) .fff-segment-item[data-segment-selected=\'true\']')
        ->toContain('.fff-segment-track.is-hydrated .fff-segment-item[data-segment-selected=\'true\']');
});
