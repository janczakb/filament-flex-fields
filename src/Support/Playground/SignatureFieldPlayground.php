<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SignatureFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'signature__contract' => null,
            'signature__webp' => null,
            'signature__readonly' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M120,180 Q180,140 260,200 Q340,120 420,190" fill="none" stroke="#18181b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Signature')
                ->description('Touch, stylus, and mouse friendly signature pad with compact SVG output, undo, clear, and fullscreen mode.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    SignatureField::make('signature__contract')
                        ->label('Sign here')
                        ->helperText('Click the pill (or select the pad and press D) to arm trackpad drawing, then glide without clicking. Lift your finger between strokes.')
                        ->trackpadGlide()
                        ->guidelines()
                        ->downloadable(SignatureField::DOWNLOAD_SVG)
                        ->downloadFilename('contract-signature')
                        ->required()
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->schema([
                            SignatureField::make('signature__webp')
                                ->label('Download as WebP')
                                ->helperText('Same pad with downloadable WebP export.')
                                ->downloadable(SignatureField::DOWNLOAD_WEBP)
                                ->webpQuality(0.88),
                            SignatureField::make('signature__readonly')
                                ->label('Read only preview')
                                ->readOnly(),
                        ]),
                ]),
        ];
    }
}
