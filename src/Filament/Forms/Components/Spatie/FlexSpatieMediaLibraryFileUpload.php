<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\InteractsWithFlexFileUpload;
use Closure;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

/**
 * Media conversions must be defined on the model via `registerMediaConversions()`.
 *
 * @see https://spatie.be/docs/laravel-medialibrary
 */
class FlexSpatieMediaLibraryFileUpload extends SpatieMediaLibraryFileUpload
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

    public function responsiveImages(bool|Closure $condition = true): static
    {
        parent::responsiveImages($condition);

        return $this;
    }

    public function conversion(string|Closure|null $conversion): static
    {
        parent::conversion($conversion);

        return $this;
    }

    public function conversionsDisk(string|Closure|null $disk): static
    {
        parent::conversionsDisk($disk);

        return $this;
    }
}
