<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Actions\Action;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard;
use Bjanczak\FilamentFlexFields\Support\Playground\CoverCardPlayground;
use Filament\Notifications\Notification;

it('exposes cover card configuration via fluent api', function () {
    $card = CoverCard::make()
        ->backgroundColor('#f4f4f5')
        ->backgroundGradient('linear-gradient(180deg, #fff, #000)')
        ->backgroundImage('https://example.com/robot.jpg')
        ->backgroundPosition('top center')
        ->ratio('3:4')
        ->topTitle('NEO')
        ->topDescription('Home Robot')
        ->footerTitle('Available soon')
        ->footerDescription('Get notified')
        ->tone('light')
        ->radius('2xl')
        ->contentMaxWidth('18rem')
        ->fullWidth();

    expect($card->getBackgroundColor())->toBe('#f4f4f5')
        ->and($card->getBackgroundGradient())->toBe('linear-gradient(180deg, #fff, #000)')
        ->and($card->getBackgroundImage())->toBe('https://example.com/robot.jpg')
        ->and($card->getBackgroundPosition())->toBe('top center')
        ->and($card->getRatio())->toBe('3:4')
        ->and($card->getAspectRatioStyle())->toBe('3 / 4')
        ->and($card->getTopTitle())->toBe('NEO')
        ->and($card->getTopDescription())->toBe('Home Robot')
        ->and($card->getFooterTitle())->toBe('Available soon')
        ->and($card->getFooterDescription())->toBe('Get notified')
        ->and($card->getTone())->toBe('light')
        ->and($card->getRadius())->toBe('2xl')
        ->and($card->getContentMaxWidth())->toBe('18rem')
        ->and($card->isFullWidth())->toBeTrue()
        ->and($card->hasTopContent())->toBeTrue()
        ->and($card->hasFooterContent())->toBeTrue();
});

it('builds layered background styles with image taking precedence over gradient', function () {
    $card = CoverCard::make()
        ->backgroundColor('#e4e4e7')
        ->backgroundGradient('linear-gradient(180deg, #fff, #000)')
        ->backgroundImage('https://example.com/robot.jpg');

    expect($card->getBackgroundStyles())->toBe([
        "background-image: url('https://example.com/robot.jpg')",
        'background-size: cover',
        'background-position: center',
        'background-repeat: no-repeat',
        'background-color: #e4e4e7',
    ]);
});

it('uses gradient background when no image is configured', function () {
    $card = CoverCard::make()
        ->backgroundColor('#18181b')
        ->backgroundGradient('linear-gradient(135deg, #111, #333)');

    expect($card->getBackgroundStyles())->toBe([
        'background-image: linear-gradient(135deg, #111, #333)',
        'background-color: #18181b',
    ]);
});

it('registers cover card playground with empty default state', function () {
    expect((new CoverCardPlayground)->defaultState())->toBe([]);
});

it('enables content overlays based on present copy blocks', function () {
    $both = CoverCard::make()
        ->contentOverlays()
        ->topTitle('Top')
        ->footerTitle('Footer');

    $topOnly = CoverCard::make()
        ->contentOverlays()
        ->topTitle('Top');

    $footerOnly = CoverCard::make()
        ->contentOverlays()
        ->footerTitle('Footer');

    expect($both->hasContentOverlays())->toBeTrue()
        ->and($both->shouldShowTopOverlay())->toBeTrue()
        ->and($both->shouldShowFooterOverlay())->toBeTrue()
        ->and($topOnly->shouldShowTopOverlay())->toBeTrue()
        ->and($topOnly->shouldShowFooterOverlay())->toBeFalse()
        ->and($footerOnly->shouldShowTopOverlay())->toBeFalse()
        ->and($footerOnly->shouldShowFooterOverlay())->toBeTrue();
});

it('allows custom top and footer overlay gradients', function () {
    $card = CoverCard::make()
        ->contentOverlays()
        ->topOverlayGradient('linear-gradient(180deg, #000, transparent)')
        ->footerOverlayGradient('linear-gradient(0deg, #111, transparent)');

    expect($card->getTopOverlayGradient())->toBe('linear-gradient(180deg, #000, transparent)')
        ->and($card->getFooterOverlayGradient())->toBe('linear-gradient(0deg, #111, transparent)');
});

it('uses glass overlay defaults when no custom gradient is configured', function () {
    $card = CoverCard::make()->contentOverlays();

    expect($card->getTopOverlayGradient())->toBe('linear-gradient(180deg, #00000036 0%, #00000021 42%, #00000000 100%)')
        ->and($card->getFooterOverlayGradient())->toBe('linear-gradient(0deg, #00000036 0%, #00000021 42%, #00000000 100%)')
        ->and($card->hasCustomTopOverlayGradient())->toBeFalse()
        ->and($card->hasCustomFooterOverlayGradient())->toBeFalse();
});

it('renders separate top and bottom overlay elements in the cover card blade', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/schemas/components/cover-card.blade.php');

    expect($blade)
        ->toContain('partials.load-stylesheet')
        ->toContain("'component' => 'cover-card'")
        ->toContain('fff-cover-card__overlay fff-cover-card__overlay--top')
        ->toContain('fff-cover-card__overlay fff-cover-card__overlay--bottom')
        ->toContain('$shouldShowTopOverlay()')
        ->toContain('$shouldShowFooterOverlay()')
        ->toContain('$hasCustomTopOverlayGradient()')
        ->toContain('$hasCustomFooterOverlayGradient()');
});

it('sanitizes unsafe background image urls', function () {
    $card = CoverCard::make()
        ->backgroundImage('javascript:alert(1)');

    expect($card->getBackgroundImage())->toBeNull()
        ->and($card->getBackgroundStyles())->toBe([]);
});

it('registers footer actions on the cover card', function () {
    $card = CoverCard::make()
        ->footerAction(
            Action::make('notify')
                ->label('Notify me')
                ->action(fn () => Notification::make()->title('Done')->success()->send()),
        );

    expect($card->getFooterAction()?->getName())->toBe('notify')
        ->and($card->hasFooterAction())->toBeTrue();
});

it('supports auto ratio without aspect style', function () {
    $card = CoverCard::make()
        ->ratio('auto');

    expect($card->getRatio())->toBeNull()
        ->and($card->getAspectRatioStyle())->toBeNull();
});

it('rejects invalid aspect ratios', function () {
    CoverCard::make()
        ->ratio('bad:0')
        ->getAspectRatioStyle();
})->throws(InvalidArgumentException::class);

it('rejects unsupported tone and radius values', function () {
    CoverCard::make()
        ->tone('neon')
        ->getTone();
})->throws(InvalidArgumentException::class);

it('rejects unsupported radius values', function () {
    CoverCard::make()
        ->radius('pill')
        ->getRadius();
})->throws(InvalidArgumentException::class);
