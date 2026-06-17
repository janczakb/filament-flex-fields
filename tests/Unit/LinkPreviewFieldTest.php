<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\LinkPreviewField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\UrlMeta\UrlMetaScraper;

it('configures preview debounce and visit link visibility', function () {
    $field = LinkPreviewField::make('article_url')
        ->previewDebounce(750)
        ->previewLayout('vertical')
        ->showVisitLink(false)
        ->prefix('https://');

    expect($field->getPreviewDebounce())->toBe(750)
        ->and($field->getPreviewLayout())->toBe('vertical')
        ->and($field->shouldShowVisitLink())->toBeFalse()
        ->and($field->getPrefix())->toBe('https://');
});

it('defaults preview min skeleton duration to 500ms', function () {
    expect(LinkPreviewField::make('article_url')->getPreviewMinSkeletonMs())->toBe(500);
});

it('configures preview min skeleton duration', function () {
    expect(LinkPreviewField::make('article_url')->previewMinSkeletonMs(300)->getPreviewMinSkeletonMs())->toBe(300);
});

it('defaults to resolving initial preview on the server', function () {
    expect(LinkPreviewField::make('article_url')->shouldResolveInitialPreviewOnServer())->toBeTrue();
});

it('can skip server-side initial preview resolution', function () {
    expect(LinkPreviewField::make('article_url')->resolveInitialPreviewOnServer(false)->shouldResolveInitialPreviewOnServer())->toBeFalse();
});

it('defaults to horizontal preview layout', function () {
    expect(LinkPreviewField::make('article_url')->getPreviewLayout())->toBe('horizontal');
});

it('accepts card preview layout', function () {
    expect(LinkPreviewField::make('article_url')->previewLayout('card')->getPreviewLayout())->toBe('card');
});

it('exposes wrapper classes and alpine configuration', function () {
    $field = LinkPreviewField::make('article_url')->previewLayout('horizontal');

    expect($field->getWrapperClasses())
        ->toHaveKey('fff-link-preview-field')
        ->toHaveKey('fff-link-preview-field--layout-horizontal')
        ->not->toHaveKey('fff-flex-text-input-field')
        ->and($field->getAlpineConfiguration())
        ->toHaveKeys(['scrapeUrl', 'previewEnabled', 'previewDebounce', 'previewMinUrlLength', 'previewMinSkeletonMs', 'previewLayout', 'showVisitLink', 'prefix', 'labels'])
        ->and($field->getAlpineConfiguration()['previewMinSkeletonMs'])->toBe(500)
        ->and($field->getAlpineConfiguration()['labels'])->toHaveKeys(['error', 'visit'])
        ->and($field->getAlpineConfiguration()['labels'])->not->toHaveKeys(['loading', 'emptyTitle', 'emptyDescription']);
});

it('resolves initial preview metadata for prefilled urls', function () {
    $this->mock(UrlMetaScraper::class, function ($mock): void {
        $mock->shouldReceive('isScrapableUrl')
            ->once()
            ->with('https://laravel.com')
            ->andReturn(true);

        $mock->shouldReceive('scrape')
            ->once()
            ->with('https://laravel.com')
            ->andReturn([
                'title' => 'Laravel',
                'description' => 'PHP framework',
                'image' => 'https://laravel.com/og.png',
            ]);
    });

    $field = LinkPreviewField::make('article_url')->default('https://laravel.com');

    expect($field->resolveInitialPreview('https://laravel.com'))
        ->toBe([
            'title' => 'Laravel',
            'description' => 'PHP framework',
            'image' => 'https://laravel.com/og.png',
        ]);
});

it('registers stylesheet dependencies', function () {
    expect(FlexFieldAssets::stylesheetsFor('link-preview-field'))
        ->toBe(['emoji-picker', 'flex-text-input', 'link-preview-field']);
});

it('rejects invalid variants', function () {
    $field = LinkPreviewField::make('article_url');
    $field->variant('invalid');

    $field->getVariant();
})->throws(InvalidArgumentException::class);

it('rejects invalid preview layouts', function () {
    $field = LinkPreviewField::make('article_url');
    $field->previewLayout('grid');

    $field->getPreviewLayout();
})->throws(InvalidArgumentException::class);

it('renders required blade integration hooks', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/link-preview-field.blade.php');

    expect($blade)
        ->toContain('wire:ignore')
        ->toContain('x-load')
        ->toContain('linkPreviewFieldFormComponent')
        ->toContain('fff-flex-text-input')
        ->toContain('fff-flex-text-input__shell')
        ->toContain('fff-link-preview__card--horizontal')
        ->toContain('fff-link-preview__card--vertical')
        ->toContain('fff-link-preview__card--card')
        ->toContain('shouldShowCard')
        ->toContain('showSkeleton')
        ->toContain('isRevealed')
        ->toContain('fff-link-preview__skeleton')
        ->toContain('onImageError()')
        ->toContain('shouldResolveInitialPreviewOnServer')
        ->toContain('aria-live="polite"')
        ->toContain('fff-link-preview__error')
        ->toContain('fff-link-preview__domain--text')
        ->toContain('GravityIcon::Paperclip')
        ->toContain('labels.visit')
        ->not->toContain('loading-indicator');
});
