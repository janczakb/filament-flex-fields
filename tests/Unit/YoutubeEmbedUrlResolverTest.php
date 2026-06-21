<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichContentRenderer;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins\FlexRichEditorYoutubePlugin;
use Bjanczak\FilamentFlexFields\Support\RichEditor\YoutubeEmbedUrlResolver;

it('validates youtube urls', function () {
    expect(YoutubeEmbedUrlResolver::isValidYoutubeUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'))->toBeTrue()
        ->and(YoutubeEmbedUrlResolver::isValidYoutubeUrl('https://youtu.be/dQw4w9WgXcQ'))->toBeTrue()
        ->and(YoutubeEmbedUrlResolver::isValidYoutubeUrl('https://example.com/video'))->toBeFalse();
});

it('resolves youtube watch urls to embed urls', function () {
    $embed = YoutubeEmbedUrlResolver::resolve(
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ['nocookie' => true],
    );

    expect($embed)->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
});

it('resolves youtube short urls to embed urls', function () {
    $embed = YoutubeEmbedUrlResolver::resolve(
        'https://youtu.be/dQw4w9WgXcQ',
        ['nocookie' => false],
    );

    expect($embed)->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ');
});

it('renders youtube nodes through flex rich content renderer', function () {
    $field = FlexRichEditor::make('body')
        ->youtube()
        ->youtubeNocookie();

    $html = FlexRichContentRenderer::make([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'youtube',
                'attrs' => [
                    'src' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'width' => 640,
                    'height' => 480,
                ],
            ],
        ],
    ])
        ->plugins([
            FlexRichEditorYoutubePlugin::make($field),
        ])
        ->toHtml();

    expect($html)
        ->toContain('data-youtube-video')
        ->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->toContain('fff-rich-editor__youtube-iframe');
});
