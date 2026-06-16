<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\LinkPreviewField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;

class LinkPreviewFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'link_preview__horizontal' => 'https://laravel.com',
            'link_preview__vertical' => 'https://filamentphp.com',
            'link_preview__card' => 'https://tailwindcss.com',
            'link_preview__soft' => 'https://tailwindcss.com',
            'link_preview__empty' => null,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Link preview field')
                ->description('FlexTextInput shell with horizontal (compact), vertical (compact card), and card (full-width social card) preview layouts. Skeleton shows only while loading — card hides when a URL has no Open Graph metadata.')
                ->schema([
                    LinkPreviewField::make('link_preview__horizontal')
                        ->label('Horizontal layout')
                        ->previewLayout('horizontal')
                        ->required(),

                    LinkPreviewField::make('link_preview__vertical')
                        ->label('Vertical layout')
                        ->previewLayout('vertical')
                        ->placeholder('https://example.com'),

                    LinkPreviewField::make('link_preview__card')
                        ->label('Card layout (full width)')
                        ->previewLayout('card')
                        ->placeholder('https://example.com'),

                    LinkPreviewField::make('link_preview__soft')
                        ->label('Soft variant + prefix')
                        ->variant('soft')
                        ->prefix('https://')
                        ->previewLayout('horizontal'),

                    LinkPreviewField::make('link_preview__empty')
                        ->label('Paste a URL to preview')
                        ->previewDebounce(400),
                ]),
        ];
    }
}
