<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\CalculatorPanelMount;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

it('queues calculator panel mount only once per request', function () {
    CalculatorPanelMount::reset();

    CalculatorPanelMount::queue();
    CalculatorPanelMount::queue();

    expect(CalculatorPanelMount::renderOnce())
        ->toContain('data-fff-calculator-panel-host')
        ->toContain('fff-calculator-panel')
        ->toContain('x-teleport="body"')
        ->and(CalculatorPanelMount::renderOnce())->toBe('');
});

it('does not render calculator panel mount when no field queued it', function () {
    CalculatorPanelMount::reset();

    expect(CalculatorPanelMount::renderOnce())->toBe('');
});

it('includes calculator panel styles in calculator field playground bundle list', function () {
    expect(FlexFieldAssets::playgroundStylesheetsFor('calculator-field'))
        ->toContain('calculator-panel');
});
