<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\InteractsWithFlexFileUpload;
use Filament\Forms\Components\FileUpload;

class FlexFileUpload extends FileUpload
{
    use HasControlSize;
    use HasFieldFocusOutline;
    use InteractsWithFlexFileUpload;

    protected string $view = 'filament-flex-fields::forms.components.flex-file-upload';

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerFlexFileUploadHooks();
    }

    public function withRecommendedDefaults(): static
    {
        $this->applyRecommendedSecurityDefaults();

        return $this->documentsOnly();
    }
}
