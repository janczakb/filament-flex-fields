<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichContentRenderer;
use Illuminate\Support\Facades\Storage;

function createFlexRichContentRendererPerformanceTestJpeg(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);

    ob_start();
    imagejpeg($image);
    $contents = ob_get_clean();
    imagedestroy($image);

    return $contents ?: '';
}

/**
 * @return array<string, mixed>
 */
function makeLargeRichEditorDocument(int $paragraphCount): array
{
    $paragraphs = [];

    for ($index = 0; $index < $paragraphCount; $index++) {
        $paragraphs[] = [
            'type' => 'paragraph',
            'content' => [
                [
                    'type' => 'text',
                    'text' => str_repeat('performance ', 40)."paragraph {$index}.",
                ],
            ],
        ];
    }

    return [
        'type' => 'doc',
        'content' => $paragraphs,
    ];
}

it('renders large rich editor documents within a production budget', function () {
    $content = makeLargeRichEditorDocument(500);

    $startedAt = hrtime(true);

    $html = FlexRichContentRenderer::make($content)->toHtml();

    $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;

    expect($html)
        ->toContain('performance')
        ->and(strlen($html))->toBeGreaterThan(10_000)
        ->and($elapsedMs)->toBeLessThan(2_000);
});

it('renders repeated image lookups using cached attachment manifests', function () {
    Storage::fake('public');

    $master = 'rich-editor/photo-large.webp';
    Storage::disk('public')->put($master, createFlexRichContentRendererPerformanceTestJpeg(400, 300));
    Storage::disk('public')->put('rich-editor/photo__thumb.webp', createFlexRichContentRendererPerformanceTestJpeg(100, 75));
    Storage::disk('public')->put('rich-editor/photo-large.flex-variants.json', json_encode([
        'master' => $master,
        'variants' => [
            'thumb' => 'rich-editor/photo__thumb.webp',
        ],
        'widths' => [
            'master' => 400,
            'thumb' => 100,
        ],
    ]));

    $content = [
        'type' => 'doc',
        'content' => array_map(fn (int $index): array => [
            'type' => 'paragraph',
            'content' => [
                [
                    'type' => 'image',
                    'attrs' => [
                        'id' => $master,
                        'alt' => "Cached image {$index}",
                    ],
                ],
            ],
        ], range(1, 25)),
    ];

    $renderer = FlexRichContentRenderer::make($content)
        ->fileAttachmentsDisk('public')
        ->responsiveImages()
        ->imageSizes('100vw');

    $startedAt = hrtime(true);
    $html = $renderer->toHtml();
    $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;

    expect($html)
        ->toContain('srcset=')
        ->and($elapsedMs)->toBeLessThan(1_500);
});
