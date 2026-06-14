<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrafficSplit;

it('exposes traffic split configuration via fluent api', function () {
    $field = TrafficSplit::make('weights')
        ->segmentCount(4)
        ->minWeight(2)
        ->size(ControlSize::Lg)
        ->variant('secondary')
        ->labels(['A', 'B', 'C', 'D']);

    expect($field->getSegmentCount())->toBe(4)
        ->and($field->getMinWeight())->toBe(2)
        ->and($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('secondary')
        ->and($field->getLabels())->toBe(['A', 'B', 'C', 'D'])
        ->and($field->getSegmentLabel(1))->toBe('B');
});

it('clamps segment count between two and five', function () {
    $field = TrafficSplit::make('weights')->segmentCount(8);

    expect($field->getSegmentCount())->toBe(5);

    $field->segmentCount(1);

    expect($field->getSegmentCount())->toBe(2);
});

it('creates an equal split that sums to one hundred', function () {
    $field = TrafficSplit::make('weights')->segmentCount(3);

    expect($field->equalSplit())->toBe([34, 33, 33])
        ->and(array_sum($field->equalSplit()))->toBe(100);

    $field->segmentCount(5);

    expect($field->equalSplit())->toBe([20, 20, 20, 20, 20]);
});

it('normalizes invalid weights back to an equal split', function () {
    $field = TrafficSplit::make('weights')->segmentCount(3);

    expect($field->normalizeWeights([50, 30, 10]))->toBe([34, 33, 33])
        ->and($field->normalizeWeights([33, 33, 34]))->toBe([33, 33, 34])
        ->and($field->normalizeWeights(null))->toBe([34, 33, 33])
        ->and($field->normalizeWeights([50, 50]))->toBe([34, 33, 33]);
});

it('defaults minimum weight to twelve percent for readable labels', function () {
    $field = TrafficSplit::make('weights')->segmentCount(3);

    expect($field->getMinWeight())->toBe(12)
        ->and($field->getValueThreshold())->toBe(18);
});

it('keeps value threshold above minimum weight', function () {
    $field = TrafficSplit::make('weights')
        ->minWeight(15)
        ->valueThreshold(16);

    expect($field->getValueThreshold())->toBe(16);

    $field->valueThreshold(10);

    expect($field->getValueThreshold())->toBe(16);
});

it('enforces minimum weight when normalizing valid totals', function () {
    $field = TrafficSplit::make('weights')
        ->segmentCount(3)
        ->minWeight(5);

    expect($field->normalizeWeights([90, 5, 5]))->toBe([90, 5, 5]);
});

it('exposes locked segment indices and preserves them when rebalancing', function () {
    $field = TrafficSplit::make('weights')
        ->segmentCount(3)
        ->lockedSegments([1]);

    expect($field->getLockedSegments())->toBe([1])
        ->and($field->isSegmentLocked(1))->toBeTrue()
        ->and($field->isSegmentLocked(0))->toBeFalse()
        ->and($field->normalizeWeights([50, 30, 10]))->toBe([35, 30, 35]);
});

it('ignores out of range locked segment indices', function () {
    $field = TrafficSplit::make('weights')
        ->segmentCount(3)
        ->lockedSegments([-1, 1, 9]);

    expect($field->getLockedSegments())->toBe([1]);
});

it('can link segment count to a repeater state path', function () {
    $field = TrafficSplit::make('split')
        ->linkedToRepeater('testing_urls');

    expect($field->isLinkedToRepeater())->toBeTrue()
        ->and($field->getLinkedRepeaterPath())->toBe('testing_urls');
});

it('detects when normalized weights already match state', function () {
    $field = TrafficSplit::make('split')->segmentCount(3);

    expect($field->weightsMatchState([33, 33, 34], [33, 33, 34]))->toBeTrue()
        ->and($field->weightsMatchState([33, 33, 34], ['33', '33', '34']))->toBeTrue()
        ->and($field->weightsMatchState([34, 33, 33], [33, 33, 34]))->toBeFalse()
        ->and($field->weightsMatchState([34, 33, 33], null))->toBeFalse()
        ->and($field->weightsMatchState([34, 33, 33], [50, 50]))->toBeFalse();
});

it('rebalances weights when segment count changes', function () {
    $field = TrafficSplit::make('split')->segmentCount(2);

    expect($field->normalizeWeights([50, 50]))->toBe([50, 50]);

    $field->segmentCount(3);

    expect($field->normalizeWeights([50, 50]))->toBe([34, 33, 33]);

    $field->segmentCount(2);

    expect($field->normalizeWeights([34, 33, 33]))->toBe([50, 50]);
});

it('builds equal splits for a segment count', function () {
    expect(TrafficSplit::equalSplitForCount(2))->toBe([50, 50])
        ->and(TrafficSplit::equalSplitForCount(3))->toBe([34, 33, 33])
        ->and(TrafficSplit::equalSplitForCount(5))->toBe([20, 20, 20, 20, 20]);
});
