<?php

namespace Filament\Forms\Components;

if (! class_exists('Filament\Forms\Components\SpatieMediaLibraryFileUpload')) {
    class SpatieMediaLibraryFileUpload extends FileUpload
    {
        public function responsiveImages(bool|\Closure $condition = true): static
        {
            return $this;
        }

        public function conversion(string|\Closure|null $conversion): static
        {
            return $this;
        }

        public function conversionsDisk(string|\Closure|null $disk): static
        {
            return $this;
        }

        public function withRecommendedDefaults(): static
        {
            return $this;
        }
    }
}
