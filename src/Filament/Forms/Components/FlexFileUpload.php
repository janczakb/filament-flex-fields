<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\InteractsWithFlexFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Closure;
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

        // Keep remove visible when image editor also adds an edit action.
        $this->removeUploadedFileButtonPosition(
            fn (FileUpload $component): string => $component->hasImageEditor() ? 'left' : 'right',
        );
        $this->registerFlexFileUploadHooks();
    }

    /**
     * Explicit disk driver sugar (path state). Default for FlexFileUpload.
     */
    public function disk(string|Closure|null $name = null): static
    {
        if ($name === null) {
            return $this;
        }

        return parent::disk($name);
    }

    /**
     * Factory sugar returning the Spatie field class (UUID state). Does not make this class bipedal.
     */
    public static function spatie(string $name): FlexSpatieMediaLibraryFileUpload
    {
        return FlexSpatieMediaLibraryFileUpload::make($name);
    }

    public function withRecommendedDefaults(): static
    {
        $this->applyRecommendedSecurityDefaults();

        return $this->documentsOnly();
    }
}
