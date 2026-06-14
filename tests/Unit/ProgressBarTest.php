<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressBar;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Playground\ProgressBarPlayground;

it('exposes progress bar configuration via fluent api', function () {
    $bar = ProgressBar::make()
        ->value(60)
        ->max(100)
        ->label('Upload')
        ->displayValue('3 of 5')
        ->showValue(true)
        ->size('md')
        ->color('primary')
        ->startMarker(GravityIcon::MapPin)
        ->currentMarker(GravityIcon::Car)
        ->endMarker(GravityIcon::House)
        ->remainingTrackStyle('dashed');

    expect($bar->getValue())->toBe(60.0)
        ->and($bar->getMax())->toBe(100.0)
        ->and($bar->getLabel())->toBe('Upload')
        ->and($bar->getDisplayValue())->toBe('3 of 5')
        ->and($bar->getFormattedValue())->toBe('3 of 5')
        ->and($bar->shouldShowValue())->toBeTrue()
        ->and($bar->getSize())->toBe('md')
        ->and($bar->getColor())->toBe('primary')
        ->and($bar->getPercentage())->toBe(60)
        ->and($bar->getProgressRatio())->toBe(0.6)
        ->and($bar->hasHeader())->toBeTrue()
        ->and($bar->hasTrackMarkers())->toBeTrue()
        ->and($bar->getRemainingTrackStyle())->toBe('dashed');
});

it('supports indeterminate progress bar mode', function () {
    $bar = ProgressBar::make()
        ->label('Loading')
        ->indeterminate();

    expect($bar->isIndeterminate())->toBeTrue()
        ->and($bar->getProgressRatio())->toBe(0.0)
        ->and($bar->hasSegments())->toBeFalse();
});

it('normalizes segmented progress bar states from active segment index', function () {
    $bar = ProgressBar::make()
        ->segments([
            ['label' => 'Ordered', 'description' => 'Done', 'icon' => GravityIcon::Check],
            ['label' => 'Shipped', 'description' => 'In transit'],
            ['label' => 'Delivered', 'description' => 'Pending', 'icon' => GravityIcon::House],
        ])
        ->activeSegment(1)
        ->activeSegmentProgress(0.62);

    $segments = $bar->getNormalizedSegments();

    expect($bar->hasSegments())->toBeTrue()
        ->and($segments)->toHaveCount(3)
        ->and($segments[0]['state'])->toBe('complete')
        ->and($segments[0]['description'])->toBe('Done')
        ->and($segments[0]['icon'])->toBe(GravityIcon::Check)
        ->and($segments[1]['state'])->toBe('active')
        ->and($segments[1]['description'])->toBe('In transit')
        ->and($segments[2]['state'])->toBe('pending')
        ->and($segments[2]['icon'])->toBe(GravityIcon::House)
        ->and($bar->hasSegmentIcons())->toBeTrue()
        ->and($bar->getActiveSegmentIndex())->toBe(1)
        ->and($bar->getActiveSegmentProgress())->toBe(0.62)
        ->and($bar->shouldShowSegmentThumb())->toBeTrue()
        ->and($bar->getSegmentFillPercentage())->toBe(54.0)
        ->and($bar->getSegmentThumbPosition())->toBe(54.0)
        ->and($bar->getSegmentFillWidthForIndex(0))->toBe(100.0)
        ->and($bar->getSegmentFillWidthForIndex(1))->toBe(62.0)
        ->and($bar->getSegmentFillWidthForIndex(2))->toBe(0.0);
});

it('derives active segment from value when no active segment is configured', function () {
    $bar = ProgressBar::make()
        ->value(75)
        ->segments(['One', 'Two', 'Three', 'Four']);

    expect($bar->getActiveSegmentIndex())->toBe(3);
});

it('can hide segment thumb explicitly', function () {
    $bar = ProgressBar::make()
        ->segments(['One', 'Two'])
        ->segmentThumb(false);

    expect($bar->shouldShowSegmentThumb())->toBeFalse();
});

it('supports checklist pill progress bar variant with auto segment count', function () {
    $bar = ProgressBar::make()
        ->variant('pills')
        ->value(23)
        ->gradientFrom('rgb(239 68 68)')
        ->gradientTo('rgb(245 158 11)')
        ->color('danger');

    expect($bar->isPillsVariant())->toBeTrue()
        ->and($bar->usesAutoPillCount())->toBeTrue()
        ->and($bar->getPillCount())->toBeNull()
        ->and($bar->getPercentage())->toBe(23)
        ->and($bar->getGradientFrom())->toBe('rgb(239 68 68)')
        ->and($bar->getGradientTo())->toBe('rgb(245 158 11)');
});

it('supports fixed pill counts when configured explicitly', function () {
    $bar = ProgressBar::make()
        ->variant('pills')
        ->value(23)
        ->pillCount(35)
        ->gradientFrom('rgb(239 68 68)')
        ->gradientTo('rgb(245 158 11)');

    expect($bar->usesAutoPillCount())->toBeFalse()
        ->and($bar->getPillCount())->toBe(35)
        ->and($bar->getActivePillCount())->toBe(8)
        ->and($bar->getPillColorForIndex(0))->toBe('rgb(239 68 68)')
        ->and($bar->getPillColorForIndex(7))->toBe('rgb(245 158 11)')
        ->and($bar->getPillColorForIndex(8))->toBeNull();
});

it('accepts hex accent colors and interpolates pill gradients from hex stops', function () {
    $bar = ProgressBar::make()
        ->color('#22c55e')
        ->variant('pills')
        ->pillCount(3)
        ->value(100)
        ->gradientFrom('#ef4444')
        ->gradientTo('#f59e0b');

    expect($bar->getColor())->toBe('#22c55e')
        ->and($bar->usesCustomAccentColor())->toBeTrue()
        ->and($bar->getColorToken())->toBeNull()
        ->and($bar->getAccentCssColor())->toBe('#22c55e')
        ->and($bar->getPillColorForIndex(0))->toBe('rgb(239 68 68)')
        ->and($bar->getPillColorForIndex(2))->toBe('rgb(245 158 11)');
});

it('supports configurable fill animation on the progress bar', function () {
    $animated = ProgressBar::make()->animated()->animationDuration(480);
    $static = ProgressBar::make()->animated(false);

    expect($animated->shouldAnimateFill())->toBeTrue()
        ->and($animated->getAnimationDuration())->toBe(480)
        ->and($static->shouldAnimateFill())->toBeFalse();
});

it('registers progress bar playground with animated fill default state', function () {
    expect((new ProgressBarPlayground)->defaultState())->toBe([
        'progress_bar__animated_value' => 35,
    ]);
});

it('renders lazy stylesheet include in progress bar blade', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/schemas/components/progress-bar.blade.php');
    $segmentsPartial = file_get_contents(__DIR__.'/../../resources/views/schemas/components/partials/progress-bar-segments.blade.php');
    $pillsPartial = file_get_contents(__DIR__.'/../../resources/views/schemas/components/partials/progress-bar-pills.blade.php');

    expect($blade)
        ->toContain('partials.load-stylesheet')
        ->toContain("'component' => 'progress-bar'")
        ->toContain('progress-bar-segments')
        ->toContain('progress-bar-pills')
        ->toContain('fff-progress-bar__card')
        ->toContain('is-indeterminate')
        ->toContain('has-custom-accent')
        ->toContain('is-fill-static')
        ->toContain('--fff-progress-fill-duration');

    expect($segmentsPartial)
        ->toContain('fff-progress-bar__segment-track')
        ->toContain('fff-progress-bar__segment-dot')
        ->toContain('fff-progress-bar__segment-icon')
        ->toContain('generate_icon_html($segment[\'icon\']');

    expect($pillsPartial)
        ->toContain('fff-progress-bar__pills--auto')
        ->toContain('ResizeObserver')
        ->toContain('pillIndices')
        ->toContain('fff-progress-bar__pill');
});

it('rejects unsupported progress bar colors', function () {
    ProgressBar::make()
        ->color('neon')
        ->getColor();
})->throws(InvalidArgumentException::class);

it('rejects invalid progress bar max values', function () {
    ProgressBar::make()
        ->max(0)
        ->getMax();
})->throws(InvalidArgumentException::class);

it('rejects unsupported remaining track styles', function () {
    ProgressBar::make()
        ->remainingTrackStyle('dotted')
        ->getRemainingTrackStyle();
})->throws(InvalidArgumentException::class);

it('rejects unsupported progress bar variants', function () {
    ProgressBar::make()
        ->variant('neon')
        ->getVariant();
})->throws(InvalidArgumentException::class);

it('rejects invalid progress bar pill counts', function () {
    ProgressBar::make()
        ->variant('pills')
        ->pillCount(0)
        ->getPillCount();
})->throws(InvalidArgumentException::class);
