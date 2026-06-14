<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressCircle;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Playground\ProgressCirclePlayground;

it('exposes progress circle configuration via fluent api', function () {
    $circle = ProgressCircle::make()
        ->value(69)
        ->max(223)
        ->displayValue('69%')
        ->fraction('124 / 223')
        ->label('Grade rating')
        ->variant('circle')
        ->size('md')
        ->color('primary')
        ->gapAngle(40)
        ->gradientFrom('rgb(99 102 241)')
        ->gradientTo('rgb(236 72 153)')
        ->contentLayout('left')
        ->shell()
        ->heading('Bounce rate')
        ->description('Visitors who leave after one page.')
        ->footer('Last 30 days');

    expect($circle->getValue())->toBe(69.0)
        ->and($circle->getMax())->toBe(223.0)
        ->and($circle->getDisplayValue())->toBe('69%')
        ->and($circle->getFormattedValue())->toBe('69%')
        ->and($circle->getFraction())->toBe('124 / 223')
        ->and($circle->getLabel())->toBe('Grade rating')
        ->and($circle->getVariant())->toBe('circle')
        ->and($circle->getSize())->toBe('md')
        ->and($circle->getColor())->toBe('primary')
        ->and($circle->getGapAngle())->toBe(40.0)
        ->and($circle->getPercentage())->toBe(31)
        ->and($circle->hasGradientStroke())->toBeTrue()
        ->and($circle->isPaused())->toBeFalse()
        ->and($circle->getContentLayout())->toBe('left')
        ->and($circle->hasShell())->toBeTrue()
        ->and($circle->getHeading())->toBe('Bounce rate')
        ->and($circle->getDescription())->toBe('Visitors who leave after one page.')
        ->and($circle->getFooter())->toBe('Last 30 days')
        ->and($circle->hasCardChrome())->toBeTrue();
});

it('supports semicircle variant and paused state', function () {
    $circle = ProgressCircle::make()
        ->value(48)
        ->variant('semicircle')
        ->gapAngle(24)
        ->paused()
        ->pausedIcon(GravityIcon::PauseFill);

    expect($circle->getVariant())->toBe('semicircle')
        ->and($circle->isPaused())->toBeTrue()
        ->and($circle->getPausedIcon())->toBe(GravityIcon::PauseFill);
});

it('computes svg metrics for circular and semicircle arcs', function () {
    $circle = ProgressCircle::make()
        ->value(50)
        ->max(100)
        ->gapAngle(40);

    $metrics = $circle->getSvgMetrics();

    expect($metrics)
        ->toHaveKeys(['radius', 'strokeWidth', 'arcLength', 'progressLength', 'rotation', 'gradientId', 'centerX', 'centerY', 'viewBox'])
        ->and($metrics['progressLength'])->toBeGreaterThan(0)
        ->and($metrics['arcLength'])->toBeGreaterThan($metrics['progressLength'])
        ->and($metrics['viewBox'])->toBe('0 0 100 100')
        ->and($metrics['centerY'])->toBe(50.0);

    $semi = ProgressCircle::make()
        ->value(50)
        ->variant('semicircle')
        ->gapAngle(20)
        ->getSvgMetrics();

    expect($semi['arcLength'])->toBeLessThan($metrics['arcLength'])
        ->and($semi['arcLength'])->toBeGreaterThan($circle->getSvgMetrics()['arcLength'] * 0.45)
        ->and($semi['viewBoxHeight'])->toBeGreaterThanOrEqual(56.0)
        ->and($semi['centerY'])->toBe(46.0)
        ->and($semi['rotation'])->toBe(175.0)
        ->and($semi['semicircleFloorInsetPercent'])->toBeGreaterThan(0);
});

it('registers progress circle playground with animated fill default state', function () {
    expect((new ProgressCirclePlayground)->defaultState())->toBe([
        'progress_circle__animated_value' => 35,
    ]);
});

it('renders lazy stylesheet include in progress circle blade', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/schemas/components/progress-circle.blade.php');
    $partial = file_get_contents(__DIR__.'/../../resources/views/schemas/components/partials/progress-circle-frame.blade.php');

    expect($blade)
        ->toContain('partials.load-stylesheet')
        ->toContain("'component' => 'progress-circle'")
        ->toContain('fff-progress-circle__card');

    expect($partial)
        ->toContain('fff-progress-circle__svg')
        ->toContain('fff-progress-circle__fill')
        ->toContain('fff-progress-circle__below-label')
        ->toContain('trackGradientId')
        ->toContain('fff-progress-circle__paused-ghost');
});

it('supports gradient strokes on fill and track', function () {
    $circle = ProgressCircle::make()
        ->gradientFrom('rgb(99 102 241)')
        ->gradientTo('rgb(236 72 153)')
        ->trackGradientFrom('rgb(228 228 231)')
        ->trackGradientTo('rgb(212 212 216)');

    expect($circle->hasGradientStroke())->toBeTrue()
        ->and($circle->hasTrackGradientStroke())->toBeTrue()
        ->and($circle->usesExplicitTrackGradient())->toBeTrue()
        ->and($circle->getTrackGradientFrom())->toBe('rgb(228 228 231)')
        ->and($circle->getTrackGradientTo())->toBe('rgb(212 212 216)');

    $implicitTrack = ProgressCircle::make()
        ->gradientFrom('rgb(99 102 241)')
        ->gradientTo('rgb(236 72 153)');

    expect($implicitTrack->usesExplicitTrackGradient())->toBeFalse()
        ->and($implicitTrack->hasTrackGradientStroke())->toBeFalse()
        ->and($implicitTrack->getTrackGradientFrom())->toBeNull()
        ->and($implicitTrack->getTrackGradientTo())->toBeNull();
});

it('accepts hex accent and gradient colors on progress circle', function () {
    $circle = ProgressCircle::make()
        ->color('#6366f1')
        ->gradientFrom('#ec4899')
        ->gradientTo('#f59e0b');

    expect($circle->getColor())->toBe('#6366f1')
        ->and($circle->usesCustomAccentColor())->toBeTrue()
        ->and($circle->getGradientFrom())->toBe('#ec4899')
        ->and($circle->getGradientTo())->toBe('#f59e0b');
});

it('supports configurable fill animation on progress circle', function () {
    $circle = ProgressCircle::make()
        ->animated()
        ->animationDuration(600);

    expect($circle->shouldAnimateFill())->toBeTrue()
        ->and($circle->getAnimationDuration())->toBe(600);
});

it('detects semicircle labels that render below the arc', function () {
    $circle = ProgressCircle::make()
        ->variant('semicircle')
        ->label('Uploading file');

    expect($circle->hasBelowLabel())->toBeTrue();

    $circleWithSideLabel = ProgressCircle::make()
        ->variant('semicircle')
        ->label('Uploading file')
        ->contentLayout('left');

    expect($circleWithSideLabel->hasBelowLabel())->toBeFalse();
});

it('rejects unsupported progress circle variants and colors', function () {
    ProgressCircle::make()
        ->variant('donut')
        ->getVariant();
})->throws(InvalidArgumentException::class);

it('rejects invalid progress circle gap angles', function () {
    ProgressCircle::make()
        ->gapAngle(360)
        ->getGapAngle();
})->throws(InvalidArgumentException::class);

it('rejects invalid progress circle max values', function () {
    ProgressCircle::make()
        ->max(-1)
        ->getMax();
})->throws(InvalidArgumentException::class);

it('rejects unsupported content layouts', function () {
    ProgressCircle::make()
        ->contentLayout('below')
        ->getContentLayout();
})->throws(InvalidArgumentException::class);
