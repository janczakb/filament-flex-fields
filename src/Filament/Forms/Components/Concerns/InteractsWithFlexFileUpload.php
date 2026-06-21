<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\FlexFileUpload\FlexFileUploadDisplay;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\FlexFileUpload\FlexFileUploadSecurity;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\FlexFileUpload\FlexFileUploadStorage;

/**
 * @mixin \Filament\Forms\Components\BaseFileUpload
 */
trait InteractsWithFlexFileUpload
{
    use FlexFileUploadDisplay;
    use FlexFileUploadSecurity;
    use FlexFileUploadStorage;
    use HasFlexFileUploadSources;

    public function withRecommendedDefaults(): static
    {
        return $this->applyRecommendedSecurityDefaults();
    }
}
