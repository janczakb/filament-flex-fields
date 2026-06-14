<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

it('exposes choice cards configuration via fluent api', function () {
    $field = ChoiceCards::make('plan')
        ->options([
            'starter' => [
                'label' => 'Starter',
                'description' => 'For individuals',
                'price' => '$5',
                'price_suffix' => '/mo',
                'icon' => GravityIcon::Cube,
                'badge' => 'Popular',
            ],
        ])
        ->layout('featured')
        ->gridColumns(['default' => 1, 'sm' => 3])
        ->indicator('check')
        ->variant('primary')
        ->color('primary')
        ->ripple()
        ->disabledOptions(['enterprise']);

    $options = $field->getNormalizedOptions();

    expect($field->getLayout())->toBe('featured')
        ->and($field->getGridColumnConfig()['sm'])->toBe(3)
        ->and($field->getIndicator())->toBe('check')
        ->and($field->getVariant())->toBe('primary')
        ->and($field->getColor())->toBe('primary')
        ->and($field->isRippleEnabled())->toBeTrue()
        ->and($options['starter']['label'])->toBe('Starter')
        ->and($options['starter']['price_suffix'])->toBe('/mo')
        ->and($options['starter']['badge'])->toBe('Popular')
        ->and($options['starter']['icon'])->toBe(GravityIcon::Cube);
});

it('accepts heroicon or any icon set in choice card options', function () {
    $field = ChoiceCards::make('payment')
        ->options([
            'card' => [
                'label' => 'Card',
                'icon' => 'heroicon-o-credit-card',
            ],
        ]);

    expect($field->getNormalizedOptions()['card']['icon'])->toBe('heroicon-o-credit-card');
});

it('resolves indicator automatically by layout', function () {
    $stack = ChoiceCards::make('plan')->layout('stack');
    $media = ChoiceCards::make('payment')->layout('media');
    $featured = ChoiceCards::make('workspace')->layout('featured');

    expect($stack->getIndicator())->toBe('radio')
        ->and($media->getIndicator())->toBe('none')
        ->and($featured->getIndicator())->toBe('check');
});

it('normalizes simple and rich options', function () {
    $field = ChoiceCards::make('region')
        ->options([
            'us-east' => 'US East',
            'eu-west' => [
                'label' => 'EU West',
                'description' => 'Lowest latency for EU users',
                'meta' => 'eu-west-1',
            ],
        ]);

    $options = $field->getNormalizedOptions();

    expect($options['us-east']['label'])->toBe('US East')
        ->and($options['eu-west']['meta'])->toBe('eu-west-1')
        ->and($field->getOptionKeys())->toBe(['us-east', 'eu-west']);
});

it('marks disabled options from fluent api and option arrays', function () {
    $field = ChoiceCards::make('plan')
        ->options([
            'starter' => ['label' => 'Starter', 'disabled' => true],
            'pro' => ['label' => 'Pro'],
        ])
        ->disabledOptions(['enterprise']);

    expect($field->isOptionDisabled('starter'))->toBeTrue()
        ->and($field->isOptionDisabled('pro'))->toBeFalse();
});

it('cascades grid column breakpoints from the largest defined value', function () {
    $field = ChoiceCards::make('delivery')
        ->gridColumns(['default' => 1, 'sm' => 3]);

    expect($field->getGridColumnConfig())->toBe([
        'default' => 1,
        'sm' => 3,
        'md' => 3,
        'lg' => 3,
    ]);
});

it('caches normalized options and evaluates options closure only once', function () {
    $evaluations = 0;
    $field = ChoiceCards::make('plan')
        ->options(function () use (&$evaluations) {
            $evaluations++;

            return [
                'starter' => 'Starter',
                'pro' => 'Pro',
            ];
        });

    expect($evaluations)->toBe(0);

    // First resolution
    $options1 = $field->getNormalizedOptions();
    expect($evaluations)->toBe(1)
        ->and($options1)->toHaveKeys(['starter', 'pro']);

    // Second resolution (should hit cache)
    $options2 = $field->getNormalizedOptions();
    expect($evaluations)->toBe(1)
        ->and($options2)->toBe($options1);
});
