<?php

namespace Filament\Forms\Components;

if (! class_exists('Filament\Forms\Components\SpatieMediaLibraryFileUpload')) {
    class SpatieMediaLibraryFileUpload extends FileUpload
    {
        protected string|\Closure|null $collection = null;

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

        public function collection(string|\Closure|null $collection): static
        {
            $this->collection = $collection;

            return $this;
        }

        /**
         * @param  array<string, mixed>|\Closure|null  $properties
         */
        public function customProperties(array|\Closure|null $properties): static
        {
            return $this;
        }

        public function withRecommendedDefaults(): static
        {
            return $this;
        }

        public function getCollection(): ?string
        {
            $value = $this->evaluate($this->collection);

            return is_string($value) ? $value : null;
        }

        public function getConversion(): ?string
        {
            return null;
        }

        public function getConversionsDisk(): ?string
        {
            return null;
        }

        /**
         * @return array<string, mixed>
         */
        public function getCustomHeaders(): array
        {
            return [];
        }

        /**
         * @return array<string, mixed>
         */
        public function getCustomProperties(): array
        {
            return [];
        }

        /**
         * @return array<string, array<string, string>>
         */
        public function getManipulations(): array
        {
            return [];
        }

        /**
         * @return array<string, mixed>
         */
        public function getProperties(): array
        {
            return [];
        }

        public function hasResponsiveImages(): bool
        {
            return false;
        }

        public function getMediaName(\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): ?string
        {
            return null;
        }
    }
}
