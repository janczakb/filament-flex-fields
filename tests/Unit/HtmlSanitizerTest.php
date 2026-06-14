<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\HtmlSanitizer;

it('strips script tags from untrusted html', function () {
    $sanitizer = new HtmlSanitizer;

    $output = $sanitizer->sanitize('<script>alert(1)</script><span class="fff-select-option">Safe</span>');

    expect($output)
        ->not->toContain('<script>')
        ->not->toContain('alert(1)')
        ->toContain('fff-select-option')
        ->toContain('Safe');
});

it('preserves safe user select markup', function () {
    $sanitizer = new HtmlSanitizer;

    $output = $sanitizer->sanitize(
        '<span class="fff-user-select-option fff-user-select-option--list">'
        .'<span class="fff-user-select__avatar fff-user-select__avatar--list">'
        .'<img src="https://example.com/jane.png" alt="" class="fff-user-select__avatar-image" />'
        .'</span>'
        .'<span class="fff-user-select-option__name">Jane Cooper</span>'
        .'</span>',
    );

    expect($output)
        ->toContain('fff-user-select-option')
        ->toContain('fff-user-select__avatar-image')
        ->toContain('Jane Cooper')
        ->toContain('https://example.com/jane.png');
});

it('returns null and empty strings unchanged', function () {
    $sanitizer = new HtmlSanitizer;

    expect($sanitizer->sanitize(null))->toBeNull()
        ->and($sanitizer->sanitize(''))->toBe('');
});
