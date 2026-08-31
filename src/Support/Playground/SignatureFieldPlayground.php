<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;
use Bjanczak\FilamentFlexFields\Support\Media\SignatureLegalPack;
use Bjanczak\FilamentFlexFields\Support\Playground\Contracts\PlaygroundWithPersistence;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SignatureFieldPlayground implements PlaygroundWithPersistence
{
    private static function readonlyPreviewSignature(): string
    {
        $path = dirname(__DIR__, 3).'/resources/fixtures/signature-readonly-preview.svg';

        if (! is_readable($path)) {
            throw new \RuntimeException("Missing signature readonly fixture at [{$path}].");
        }

        return trim((string) file_get_contents($path));
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'signature__contract' => null,
            'signature__enterprise' => null,
            'signature__enterprise_legal' => null,
            'signature__validation' => null,
            'signature__webp' => null,
            'signature__readonly' => self::readonlyPreviewSignature(),
        ];
    }

    public function playgroundSlug(): string
    {
        return 'signature-field';
    }

    /**
     * @return list<string>
     */
    public function persistedStateKeys(): ?array
    {
        return [
            'signature__contract',
            'signature__enterprise',
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
            Section::make('Enterprise persistence')
                ->description('Use Validate, then Save to storage. Inline SVG is written to disk as an ffstage: token and reloaded after refresh. SignatureLegalPack::requiresInk() gates empty submissions.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    SignatureField::make('signature__enterprise')
                        ->label('Contract signature (disk)')
                        ->helperText('Requires at least 2 strokes. Saved state survives page reload via playground storage + disk token.')
                        ->storeToDisk('flex-fields-playground/signatures')
                        ->legalPack()
                        ->timestampSeal()
                        ->legalMetadataIn('signature__enterprise_legal')
                        ->inkTrail()
                        ->pdfPreview()
                        ->minStrokes(2)
                        ->maxSizeKb(64)
                        ->required()
                        ->downloadable(SignatureField::DOWNLOAD_SVG)
                        ->downloadFilename('enterprise-contract')
                        ->columnSpanFull(),
                    SignatureField::make('signature__validation')
                        ->label('Stroke validation demo')
                        ->helperText('Try Validate with a single stroke — minStrokes(2) fails server-side and shows inline feedback.')
                        ->minStrokes(2)
                        ->guidelines(),
                ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function sealPersistedState(array $state): array
    {
        if (! SignatureLegalPack::requiresInk($state['signature__enterprise'] ?? null)) {
            return $state;
        }

        $state['_meta'] = SignatureLegalPack::legalAuditSeal($state['signature__enterprise'] ?? null);

        return $state;
    }
}
