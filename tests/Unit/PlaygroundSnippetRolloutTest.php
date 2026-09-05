<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Bjanczak\FilamentFlexFields\Support\Playground\ColorSwatchPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FocusOutlinePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\PlaygroundCodeSnippet;
use Bjanczak\FilamentFlexFields\Support\Playground\PlaygroundUsageExporter;
use Bjanczak\FilamentFlexFields\Support\Playground\SegmentTabsPlayground;

it('auto-appends starter snippets for hubs without hand-written examples', function (): void {
    $withCustomSnippet = 0;

    foreach (FlexFieldsPlaygroundRegistry::definitions() as $definition) {
        if (PlaygroundCodeSnippet::playgroundDeclaresSnippet($definition['playground'])) {
            $withCustomSnippet++;
        }
    }

    $total = count(FlexFieldsPlaygroundRegistry::definitions());
    $autoCovered = $total - $withCustomSnippet;

    expect($total)->toBeGreaterThanOrEqual(62)
        ->and($withCustomSnippet)->toBeGreaterThanOrEqual(8)
        ->and($autoCovered + $withCustomSnippet)->toBe($total)
        ->and($autoCovered)->toBeGreaterThanOrEqual(40);
});

it('builds hub usage snippets from live field components', function (): void {
    $snippet = PlaygroundCodeSnippet::forHub('color-swatch', ColorSwatchPlayground::class);
    $code = $snippet->getViewData()['tabs'][0]['code'] ?? '';

    expect($snippet->getView())->toBe('filament-flex-fields::partials.playground.code-snippet')
        ->and($code)->toContain('ColorSwatchField::make(')
        ->and($code)->toContain('->colors(')
        ->and($code)->not->toContain('FlexFieldsPlaygroundRegistry')
        ->and($code)->not->toContain('Playground hub:');
});

it('exports sanitized field names without playground prefixes', function (): void {
    $code = PlaygroundUsageExporter::fromComponents((new ColorSwatchPlayground)->components());

    expect($code)
        ->toContain("ColorSwatchField::make('sm')")
        ->not->toContain('color_swatch__');
});

it('exports SegmentTabs for the segment-tabs hub instead of a lone nested input', function (): void {
    $code = PlaygroundUsageExporter::fromComponents(
        (new SegmentTabsPlayground)->components(),
        'segment-tabs',
    );

    expect($code)
        ->toContain('SegmentTabs::make(')
        ->toContain('SegmentTab::make(')
        ->toContain('->tabs([')
        ->toContain('FlexTextInput::make(')
        ->toContain("SegmentTabs::make('Account')");
});

it('ships a hand-written SegmentTabs usage snippet on the hub', function (): void {
    expect(PlaygroundCodeSnippet::playgroundDeclaresSnippet(SegmentTabsPlayground::class))->toBeTrue();

    $components = (new SegmentTabsPlayground)->components();
    $snippet = collect($components)->first(
        fn ($component): bool => $component instanceof \Filament\Schemas\Components\View
            && $component->getView() === 'filament-flex-fields::partials.playground.code-snippet',
    );

    expect($snippet)->not->toBeNull();

    $code = $snippet->getViewData()['tabs'][0]['code'] ?? '';

    expect($code)
        ->toContain('SegmentTabs::make(')
        ->toContain('GravityIcon::Person')
        ->not->toContain('FlexTextInput::make(\'general_name\')');
});

it('never emits registry boilerplate in auto hub snippets', function (): void {
    $snippet = PlaygroundCodeSnippet::forHub('focus-outline', FocusOutlinePlayground::class);
    $code = $snippet->getViewData()['tabs'][0]['code'] ?? '';

    expect($code)
        ->not->toContain('FlexFieldsPlaygroundRegistry')
        ->not->toContain('\$playground->components()');
});
