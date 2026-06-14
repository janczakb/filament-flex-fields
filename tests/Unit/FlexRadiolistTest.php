<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist;
use Illuminate\Validation\Rules\In;

it('exposes flex radiolist configuration via fluent api', function () {
    $field = FlexRadiolist::make('delivery')
        ->options([
            'standard' => 'Standard',
            'express' => 'Express',
        ])
        ->icons([
            'standard' => 'heroicon-o-truck',
            'express' => 'heroicon-o-bolt',
        ])
        ->descriptions([
            'standard' => '4–10 business days',
        ])
        ->disabledOptions(['archived'])
        ->size('lg');

    $options = $field->getNormalizedOptions();

    expect($options['standard']['label'])->toBe('Standard')
        ->and($options['standard']['icon'])->toBe('heroicon-o-truck')
        ->and($options['standard']['description'])->toBe('4–10 business days')
        ->and($field->getSize())->toBe('lg');
});

it('marks disabled options from disabledOptions helper', function () {
    $field = FlexRadiolist::make('delivery')
        ->options([
            'standard' => 'Standard',
            'archived' => 'Archived',
        ])
        ->disabledOptions(['archived']);

    $options = $field->getNormalizedOptions();

    expect($options['standard']['disabled'])->toBeFalse()
        ->and($options['archived']['disabled'])->toBeTrue()
        ->and($field->isOptionDisabled('archived'))->toBeTrue();
});

it('validates selected value is one of the options', function () {
    $field = FlexRadiolist::make('delivery')
        ->options(['standard' => 'Standard', 'express' => 'Express']);

    $rules = $field->getValidationRules();
    $inRule = collect($rules)->first(fn ($rule) => $rule instanceof In || (is_string($rule) && str_starts_with($rule, 'in:')));

    expect($inRule)->not->toBeNull()
        ->and((string) $inRule)->toBe('in:"standard","express"');
});

it('supports sm md and lg sizes', function () {
    expect(FlexRadiolist::make('delivery')->size('sm')->getSize())->toBe('sm')
        ->and(FlexRadiolist::make('delivery')->size('md')->getSize())->toBe('md')
        ->and(FlexRadiolist::make('delivery')->size('lg')->getSize())->toBe('lg');
});

it('maps checklist size css variables to radiolist variables', function () {
    $styles = FlexRadiolist::make('delivery')->size('sm')->getRadiolistSizeStyles();

    expect($styles)->toHaveKey('--fff-flex-radiolist-label-size')
        ->and($styles['--fff-flex-radiolist-label-size'])->toBe('0.8125rem')
        ->not->toHaveKey('--fff-flex-checklist-label-size');
});

it('defaults to primary color wrapper classes', function () {
    $field = FlexRadiolist::make('delivery');

    expect($field->getColor())->toBe('primary')
        ->and($field->getWrapperClasses())->toBe([
            'fff-flex-radiolist',
            'fff-flex-radiolist--md',
            'fff-flex-radiolist--default',
            'fi-color-primary',
        ]);
});

it('supports label-only variant with radio and label only', function () {
    $field = FlexRadiolist::make('files')
        ->variant('label-only')
        ->options(['proposal' => 'Project proposal.pdf'])
        ->icons(['proposal' => 'heroicon-o-document'])
        ->descriptions(['proposal' => 'Updated yesterday']);

    expect($field->getVariant())->toBe('label-only')
        ->and($field->isLabelOnlyVariant())->toBeTrue()
        ->and($field->getWrapperClasses())->toContain('fff-flex-radiolist--label-only')
        ->and($field->getNormalizedOptions()['proposal'])->toBe([
            'label' => 'Project proposal.pdf',
            'description' => null,
            'icon' => null,
            'disabled' => false,
        ]);
});

it('rejects unsupported flex radiolist variants', function () {
    FlexRadiolist::make('files')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('allows options without icons or descriptions', function () {
    $field = FlexRadiolist::make('permissions')
        ->options([
            'read' => 'Read',
            'write' => 'Write',
        ]);

    $options = $field->getNormalizedOptions();

    expect($options['read']['icon'])->toBeNull()
        ->and($options['read']['description'])->toBeNull()
        ->and($options['write']['label'])->toBe('Write');
});

it('accepts desc as an inline option alias for description', function () {
    $field = FlexRadiolist::make('delivery')
        ->options([
            'standard' => [
                'label' => 'Standard',
                'desc' => '4–10 business days',
            ],
        ]);

    expect($field->getNormalizedOptions()['standard']['description'])->toBe('4–10 business days');
});

it('loads shared flex checklist stylesheet from the radiolist blade', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-radiolist.blade.php');

    expect($blade)
        ->toContain('partials.load-stylesheet')
        ->and($blade)->toContain("'component' => 'flex-radiolist'");
});
