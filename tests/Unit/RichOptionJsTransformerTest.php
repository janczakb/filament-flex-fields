<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\HtmlSanitizer;
use Bjanczak\FilamentFlexFields\Support\Select\RichOptionJsTransformer;

it('adds lean metadata for client renderable rich options', function () {
    $transformer = new RichOptionJsTransformer(new HtmlSanitizer);

    $options = $transformer->transform(
        [
            'ocean' => [
                'label' => 'Ocean',
                'image' => 'https://example.com/ocean.png',
            ],
        ],
        fn (string|int $value, array|string $label): array => [
            'value' => $value,
            'label' => is_string($label) ? $label : (string) ($label['label'] ?? $value),
            'description' => is_array($label) ? ($label['description'] ?? null) : null,
            'icon' => is_array($label) ? ($label['icon'] ?? null) : null,
            'image' => is_array($label) ? ($label['image'] ?? null) : null,
            'badge' => is_array($label) ? ($label['badge'] ?? null) : null,
            'badge_color' => is_array($label) ? ($label['badge_color'] ?? null) : null,
            'disabled' => false,
            'verified' => is_array($label) ? (bool) ($label['verified'] ?? false) : false,
        ],
        fn (array $option, bool $compact = false): string => $option['label'],
        fn (): bool => false,
        fn (array $option): bool => array_key_exists('label', $option),
        fn (?string $html): ?string => $html,
        'list',
    );

    expect($options[0])
        ->toMatchArray([
            'value' => 'ocean',
            'label' => 'Ocean',
            'fffClientRender' => true,
            'image' => 'https://example.com/ocean.png',
        ]);
});

it('keeps html labels for rich options with badges via select field', function () {
    $field = SelectField::make('plan')
        ->richOptions()
        ->options([
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced plan',
                'badge' => 'Popular',
            ],
        ]);

    $options = $field->getOptionsForJs();

    expect($options[0]['label'])->toContain('fff-select-option')
        ->and($options[0])->not->toHaveKey('fffClientRender');
});

it('sanitizes malicious html in select field js options when html is allowed', function () {
    $field = SelectField::make('status')
        ->allowHtml()
        ->options([
            'xss' => '<script>alert(1)</script><span>Safe label</span>',
        ]);

    $options = $field->getOptionsForJs();

    expect($options[0]['label'])
        ->not->toContain('<script>')
        ->toContain('Safe label');
});
