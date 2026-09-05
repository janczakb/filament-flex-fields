<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Playground\FieldIntelligencePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\PlaygroundCodeSnippet;
use Filament\Schemas\Components\View;

it('builds a playground view component with highlighted php', function (): void {
    $snippet = PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;

NumberStepper::make('nights');
PHP);

    expect($snippet)->toBeInstanceOf(View::class)
        ->and($snippet->getView())->toBe('filament-flex-fields::partials.playground.code-snippet');
});

it('builds tabbed snippets with php and json panes', function (): void {
    $snippet = PlaygroundCodeSnippet::tabs([
        'PHP' => "FormulaEngine::evaluate('{a}*{b}', ['a' => 1, 'b' => 2]);",
        'JSON' => '{"formula": "{a}*{b}"}',
    ], filename: 'demo');

    expect($snippet)->toBeInstanceOf(View::class);
});

it('highlights php keywords methods strings comments and numbers', function (): void {
    $html = PlaygroundCodeSnippet::highlightPhp(<<<'PHP'
// enable formulas
use Foo\Bar;

Bar::make('code')->label('Hi');
$value = 42;
PHP);

    expect($html)
        ->toContain('<span class="fff-token-comment">')
        ->toContain('<span class="fff-token-keyword">use</span>')
        ->toContain('<span class="fff-token-method">make</span>')
        ->toContain('<span class="fff-token-method">label</span>')
        ->toContain('<span class="fff-token-string">&#039;code&#039;</span>')
        ->toContain('<span class="fff-token-number">42</span>')
        ->toContain('<span class="fff-token-variable">$value</span>')
        ->and($html)->not->toContain('fff-token-keyword">Foo')
        ->and($html)->not->toContain('&amp;gt;')
        ->and($html)->toContain('-&gt;<span class="fff-token-method">label</span>');
});

it('highlights ->default as a method not a leftover placeholder', function (): void {
    $html = PlaygroundCodeSnippet::highlightPhp(<<<'PHP'
ColorSwatchField::make('color')->default('green');
PHP);

    expect($html)
        ->toContain('<span class="fff-token-method">default</span>')
        ->toContain('<span class="fff-token-string">&#039;green&#039;</span>')
        ->not->toContain('FFF_TK_')
        ->not->toContain('§ph')
        ->not->toContain('fff-token-keyword">default');
});

it('highlights json properties strings and literals', function (): void {
    $html = PlaygroundCodeSnippet::highlightJson(<<<'JSON'
{"slug": "vat", "enabled": true, "rate": 23}
JSON);

    expect($html)
        ->toContain('<span class="fff-token-property">')
        ->toContain('<span class="fff-token-string">')
        ->toContain('<span class="fff-token-keyword">true</span>')
        ->toContain('<span class="fff-token-number">23</span>');
});

it('embeds copyable usage snippets under calculated formula scenarios', function (): void {
    $components = (new FieldIntelligencePlayground)->components();

    $snippetViews = collect($components)
        ->flatMap(fn ($section) => $section->getDefaultChildComponents())
        ->filter(fn ($component): bool => $component instanceof View
            && $component->getView() === 'filament-flex-fields::partials.playground.code-snippet');

    expect($snippetViews)->toHaveCount(8);
});
