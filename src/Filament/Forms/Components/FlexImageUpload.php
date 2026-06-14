<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

class FlexImageUpload extends FlexFileUpload
{
    public function withRecommendedDefaults(): static
    {
        $this->applyRecommendedSecurityDefaults();

        return $this->imagesOnly();
    }
}
