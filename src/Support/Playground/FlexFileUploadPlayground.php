<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexImageUpload;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class FlexFileUploadPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'file_upload__avatar' => null,
            'file_upload__documents' => null,
            'file_upload__images' => null,
            'file_upload__multi' => [],
            'file_upload__metadata' => null,
            'file_upload__metadata_meta' => null,
            'file_upload__variant_primary' => null,
            'file_upload__variant_secondary' => null,
            'file_upload__variant_flat' => null,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('File upload')
                ->description('FlexFileUpload with security defaults, MIME presets, summaries, and optional metadata sidecars.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    FlexImageUpload::make('file_upload__avatar')
                        ->label('Avatar')
                        ->helperText('Circular profile photo with 1:1 crop, image editor, and circle cropper.')
                        ->withRecommendedDefaults()
                        ->avatar()
                        ->imageEditor()
                        ->circleCropper()
                        ->disk('local')
                        ->directory('playground/uploads/avatars')
                        ->maxFiles(1)
                        ->optimizeImages()
                        ->maxImageWidth(512)
                        ->maxImageHeight(512)
                        ->emptyStateHint('Upload profile photo'),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->schema([
                            FlexFileUpload::make('file_upload__documents')
                                ->label('Documents')
                                ->helperText('Recommended defaults with documentsOnly(), replace confirmation, and upload summary.')
                                ->withRecommendedDefaults()
                                ->disk('local')
                                ->directory('playground/uploads/documents')
                                ->uploadSummary()
                                ->requireReplaceConfirmation()
                                ->remainingSlotsLabel()
                                ->maxFiles(1)
                                ->showFileIcon()
                                ->compactList(),
                            FlexImageUpload::make('file_upload__images')
                                ->label('Images')
                                ->helperText('Image preset with optimization hooks enabled.')
                                ->withRecommendedDefaults()
                                ->disk('local')
                                ->directory('playground/uploads/images')
                                ->imagePreviewHeight('150')
                                ->optimizeImages()
                                ->maxImageWidth(1600)
                                ->uploadSummary(),
                        ]),
                    FlexFileUpload::make('file_upload__multi')
                        ->label('Multiple files')
                        ->helperText('Multiple uploads with total size guard and remaining slot label.')
                        ->withRecommendedDefaults()
                        ->disk('local')
                        ->directory('playground/uploads/multi')
                        ->multiple()
                        ->maxFiles(5)
                        ->maxTotalSizeKb(10240)
                        ->remainingSlotsLabel()
                        ->uploadSummary()
                        ->columnSpanFull(),
                    FlexFileUpload::make('file_upload__metadata')
                        ->label('With metadata sidecar')
                        ->helperText('Stores original name, mime, size, and dimensions in a sibling state path.')
                        ->withRecommendedDefaults()
                        ->disk('local')
                        ->directory('playground/uploads/metadata')
                        ->storeMetadataIn('file_upload__metadata_meta')
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 3])
                        ->schema([
                            FlexFileUpload::make('file_upload__variant_primary')
                                ->label('Primary')
                                ->variant('primary')
                                ->documentsOnly()
                                ->disk('local')
                                ->directory('playground/uploads/variants')
                                ->emptyStateHint('Drop a PDF here')
                                ->maxFiles(1)
                                ->compactList()
                                ->showFileIcon(),
                            FlexFileUpload::make('file_upload__variant_secondary')
                                ->label('Secondary')
                                ->variant('secondary')
                                ->size('sm')
                                ->documentsOnly()
                                ->disk('local')
                                ->directory('playground/uploads/variants')
                                ->maxFiles(1)
                                ->compactList()
                                ->showFileIcon(),
                            FlexFileUpload::make('file_upload__variant_flat')
                                ->label('Flat')
                                ->variant('flat')
                                ->size('lg')
                                ->documentsOnly()
                                ->disk('local')
                                ->directory('playground/uploads/variants')
                                ->maxFiles(1)
                                ->compactList()
                                ->showFileIcon(),
                        ]),
                ]),
        ];
    }
}
