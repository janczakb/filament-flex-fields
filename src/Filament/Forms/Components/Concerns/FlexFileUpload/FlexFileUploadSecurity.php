<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\FlexFileUpload;

use Bjanczak\FilamentFlexFields\Support\FileUpload\ExecutableExtensionGuard;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadMimePresets;
use Bjanczak\FilamentFlexFields\Support\Translations;
use Closure;
use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @mixin BaseFileUpload
 */
trait FlexFileUploadSecurity
{
    protected int|Closure|null $flexMinImageWidth = null;

    protected int|Closure|null $flexMinImageHeight = null;

    protected int|Closure|null $flexMaxImageWidth = null;

    protected int|Closure|null $flexMaxImageHeight = null;

    protected bool|Closure $flexOptimizeImages = false;

    protected bool|Closure $flexOptimizeImagesToWebp = false;

    protected bool|Closure $flexStripExif = true;

    protected bool|Closure $flexRejectExecutableFiles = false;

    /**
     * @var list<string>|null
     */
    protected ?array $flexAllowedExtensions = null;

    protected bool $flexRecommendedDefaultsApplied = false;

    public function applyRecommendedSecurityDefaults(): static
    {
        $this->flexRecommendedDefaultsApplied = true;

        $this
            ->createFormStrategy()
            ->deleteFileOnRemove()
            ->deleteReplacedFiles()
            ->maxSize(5120)
            ->downloadable()
            ->openable()
            ->focusOutline();

        return $this;
    }

    public function documentsOnly(): static
    {
        return $this
            ->acceptedFileTypes(FileUploadMimePresets::documents())
            ->validationMessages([
                'mimetypes' => Translations::get('filament-flex-fields::default.file_upload.validation.documents_only'),
            ]);
    }

    public function imagesOnly(): static
    {
        return $this
            ->acceptedFileTypes(FileUploadMimePresets::images())
            ->validationMessages([
                'mimetypes' => Translations::get('filament-flex-fields::default.file_upload.validation.images_only'),
            ]);
    }

    public function spreadsheetsOnly(): static
    {
        return $this
            ->acceptedFileTypes(FileUploadMimePresets::spreadsheets())
            ->validationMessages([
                'mimetypes' => Translations::get('filament-flex-fields::default.file_upload.validation.spreadsheets_only'),
            ]);
    }

    /**
     * @param  list<string>  $extensions
     */
    public function allowedExtensions(array $extensions): static
    {
        $this->flexAllowedExtensions = array_values(array_filter(array_map(
            static fn (string $extension): string => strtolower(ltrim($extension, '.')),
            $extensions,
        )));

        $this->rule(static function (BaseFileUpload $component): Closure {
            /** @var static $component */
            $allowed = $component->getAllowedExtensions();

            return static function (string $attribute, mixed $value, Closure $fail) use ($allowed): void {
                if (blank($value)) {
                    return;
                }

                $filename = $value instanceof TemporaryUploadedFile
                    ? $value->getClientOriginalName()
                    : basename((string) $value);

                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (! in_array($extension, $allowed, true)) {
                    $fail(__('filament-flex-fields::default.file_upload.validation.extension_not_allowed', [
                        'extensions' => implode(', ', $allowed),
                    ]));
                }
            };
        });

        return $this;
    }

    public function rejectExecutableFiles(bool|Closure $condition = true): static
    {
        $this->flexRejectExecutableFiles = $condition;

        $this->rule(static function (BaseFileUpload $component): ?Closure {
            /** @var static $component */
            if (! $component->shouldRejectExecutableFiles()) {
                return null;
            }

            return ExecutableExtensionGuard::validationRule();
        });

        return $this;
    }

    public function minImageDimensions(int $width, int $height): static
    {
        $this->flexMinImageWidth = $width;
        $this->flexMinImageHeight = $height;

        $this->registerImageDimensionRule();

        return $this;
    }

    public function maxImageDimensions(int $width, int $height): static
    {
        $this->flexMaxImageWidth = $width;
        $this->flexMaxImageHeight = $height;

        $this->registerImageDimensionRule();

        return $this;
    }

    public function optimizeImages(bool|Closure $condition = true): static
    {
        $this->flexOptimizeImages = $condition;

        return $this;
    }

    public function optimizeImagesToWebp(bool|Closure $condition = true): static
    {
        $this->flexOptimizeImagesToWebp = $condition;

        return $this;
    }

    public function maxImageWidth(int|Closure $width): static
    {
        $this->flexMaxImageWidth = $width;

        return $this;
    }

    public function maxImageHeight(int|Closure $height): static
    {
        $this->flexMaxImageHeight = $height;

        return $this;
    }

    public function stripExif(bool|Closure $condition = true): static
    {
        $this->flexStripExif = $condition;

        return $this;
    }

    protected function registerImageDimensionRule(): void
    {
        $this->rule(static function (BaseFileUpload $component): ?Closure {
            /** @var static $component */
            $minWidth = $component->getFlexMinImageWidth();
            $minHeight = $component->getFlexMinImageHeight();
            $maxWidth = $component->getFlexMaxImageWidth();
            $maxHeight = $component->getFlexMaxImageHeight();

            if (! $minWidth && ! $minHeight && ! $maxWidth && ! $maxHeight) {
                return null;
            }

            $rule = Rule::dimensions();

            if ($minWidth) {
                $rule->minWidth($minWidth);
            }

            if ($minHeight) {
                $rule->minHeight($minHeight);
            }

            if ($maxWidth) {
                $rule->maxWidth($maxWidth);
            }

            if ($maxHeight) {
                $rule->maxHeight($maxHeight);
            }

            return static function (string $attribute, mixed $value, Closure $fail) use ($rule): void {
                if (blank($value)) {
                    return;
                }

                if (validator(['file' => $value], ['file' => $rule])->fails()) {
                    $fail(__('filament-flex-fields::default.file_upload.validation.image_dimensions'));
                }
            };
        });
    }

    public function shouldOptimizeImages(): bool
    {
        return (bool) $this->evaluate($this->flexOptimizeImages);
    }

    public function shouldOptimizeImagesToWebp(): bool
    {
        return (bool) $this->evaluate($this->flexOptimizeImagesToWebp);
    }

    public function shouldStripExif(): bool
    {
        return (bool) $this->evaluate($this->flexStripExif);
    }

    public function shouldRejectExecutableFiles(): bool
    {
        return (bool) $this->evaluate($this->flexRejectExecutableFiles);
    }

    public function getFlexMinImageWidth(): ?int
    {
        $value = $this->evaluate($this->flexMinImageWidth);

        return is_numeric($value) ? (int) $value : null;
    }

    public function getFlexMinImageHeight(): ?int
    {
        $value = $this->evaluate($this->flexMinImageHeight);

        return is_numeric($value) ? (int) $value : null;
    }

    public function getFlexMaxImageWidth(): ?int
    {
        $value = $this->evaluate($this->flexMaxImageWidth);

        return is_numeric($value) ? (int) $value : null;
    }

    public function getFlexMaxImageHeight(): ?int
    {
        $value = $this->evaluate($this->flexMaxImageHeight);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return list<string>
     */
    public function getAllowedExtensions(): array
    {
        return $this->flexAllowedExtensions ?? [];
    }
}
