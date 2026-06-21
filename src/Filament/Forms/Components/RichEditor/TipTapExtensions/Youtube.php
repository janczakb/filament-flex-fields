<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\TipTapExtensions;

use Bjanczak\FilamentFlexFields\Support\RichEditor\YoutubeEmbedUrlResolver;
use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

class Youtube extends Node
{
    public static $name = 'youtube';

    public function addOptions()
    {
        return [
            'nocookie' => true,
            'width' => 640,
            'height' => 480,
            'allowFullscreen' => true,
            'controls' => true,
            'HTMLAttributes' => [
                'class' => 'fff-rich-editor__youtube-iframe',
            ],
        ];
    }

    public function parseHTML()
    {
        return [
            [
                'tag' => 'div[data-youtube-video] iframe',
            ],
        ];
    }

    public function addAttributes()
    {
        return [
            'src' => [],
            'start' => [],
            'width' => [],
            'height' => [],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = [])
    {
        $attributes = (array) ($node->attrs ?? []);
        $embedUrl = YoutubeEmbedUrlResolver::resolve(
            $attributes['src'] ?? null,
            [
                ...$this->options,
                'startAt' => (int) ($attributes['start'] ?? 0),
            ],
        );

        if ($embedUrl === null) {
            return ['div', ['data-youtube-video' => ''], 0];
        }

        $width = $attributes['width'] ?? $this->options['width'];
        $height = $attributes['height'] ?? $this->options['height'];

        return [
            'div',
            ['data-youtube-video' => 'true'],
            [
                'iframe',
                HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes, [
                    'src' => $embedUrl,
                    'width' => $width,
                    'height' => $height,
                    'allowfullscreen' => ($this->options['allowFullscreen'] ?? true) ? 'true' : null,
                ]),
            ],
        ];
    }
}
