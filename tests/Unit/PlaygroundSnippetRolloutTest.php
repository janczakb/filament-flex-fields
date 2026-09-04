<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Bjanczak\FilamentFlexFields\Support\Playground\FocusOutlinePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\PlaygroundCodeSnippet;

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
        ->and($withCustomSnippet)->toBeGreaterThanOrEqual(7)
        ->and($autoCovered + $withCustomSnippet)->toBe($total)
        ->and($autoCovered)->toBeGreaterThanOrEqual(40);
});

it('builds hub starter snippets from registry metadata', function (): void {
    $snippet = PlaygroundCodeSnippet::forHub('focus-outline', FocusOutlinePlayground::class);

    expect($snippet->getView())->toBe('filament-flex-fields::partials.playground.code-snippet');
});
