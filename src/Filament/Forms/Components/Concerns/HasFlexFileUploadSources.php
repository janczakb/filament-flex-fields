<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Bjanczak\FilamentFlexFields\Enums\FileUploadSource;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadImportConstraints;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadImportException;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadRemoteImporter;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Renderless;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Optional upload source tabs (file, URL, webcam) for {@see BaseFileUpload} flex fields.
 *
 * @mixin BaseFileUpload
 */
trait HasFlexFileUploadSources
{
    protected bool|Closure $flexAllowUrlUpload = false;

    protected bool|Closure $flexAllowWebcamUpload = false;

    protected string|Closure $flexUploadSourceTabsVariant = 'ghost';

    protected string|Closure|null $flexUploadSourceTabsColor = 'primary';

    /**
     * @var array<string, string|Closure>|Closure|null
     */
    protected array|Closure|null $flexUploadSourceTabLabels = null;

    public function allowUrlUpload(bool|Closure $condition = true): static
    {
        $this->flexAllowUrlUpload = $condition;

        return $this;
    }

    public function allowWebcamUpload(bool|Closure $condition = true): static
    {
        $this->flexAllowWebcamUpload = $condition;

        return $this;
    }

    public function uploadSourceTabsVariant(string|Closure $variant): static
    {
        $this->flexUploadSourceTabsVariant = $variant;

        return $this;
    }

    public function uploadSourceTabsColor(string|Closure|null $color): static
    {
        $this->flexUploadSourceTabsColor = $color;

        return $this;
    }

    /**
     * @param  array<string, string|Closure>  $labels
     */
    public function uploadSourceTabLabels(array|Closure $labels): static
    {
        $this->flexUploadSourceTabLabels = $labels;

        return $this;
    }

    public function shouldAllowUrlUpload(): bool
    {
        return (bool) $this->evaluate($this->flexAllowUrlUpload);
    }

    public function shouldAllowWebcamUpload(): bool
    {
        if (! (bool) $this->evaluate($this->flexAllowWebcamUpload)) {
            return false;
        }

        return $this->supportsWebcamCapture();
    }

    public function hasUploadSourceTabs(): bool
    {
        return $this->shouldAllowUrlUpload() || $this->shouldAllowWebcamUpload();
    }

    public function getUploadSourceTabsVariant(): string
    {
        $variant = (string) $this->evaluate($this->flexUploadSourceTabsVariant);

        if (! in_array($variant, ['default', 'ghost'], true)) {
            throw new InvalidArgumentException("Invalid upload source tabs variant [{$variant}].");
        }

        return $variant;
    }

    public function getUploadSourceTabsColor(): ?string
    {
        $color = $this->evaluate($this->flexUploadSourceTabsColor);

        return filled($color) ? (string) $color : null;
    }

    /**
     * @return list<FileUploadSource>
     */
    public function getEnabledUploadSources(): array
    {
        if (! $this->hasUploadSourceTabs()) {
            return [FileUploadSource::File];
        }

        $sources = [FileUploadSource::File];

        if ($this->shouldAllowUrlUpload()) {
            $sources[] = FileUploadSource::Url;
        }

        if ($this->shouldAllowWebcamUpload()) {
            $sources[] = FileUploadSource::Webcam;
        }

        return $sources;
    }

    /**
     * @return list<string>
     */
    public function getUploadSourceTabKeys(): array
    {
        return array_map(
            static fn (FileUploadSource $source): string => $source->value,
            $this->getEnabledUploadSources(),
        );
    }

    public function getUploadSourceTabLabel(FileUploadSource $source): string
    {
        $labels = $this->evaluate($this->flexUploadSourceTabLabels);

        if (is_array($labels) && filled($labels[$source->value] ?? null)) {
            return (string) $this->evaluate($labels[$source->value]);
        }

        return match ($source) {
            FileUploadSource::File => __('filament-flex-fields::default.file_upload.sources.tabs.file'),
            FileUploadSource::Url => __('filament-flex-fields::default.file_upload.sources.tabs.url'),
            FileUploadSource::Webcam => __('filament-flex-fields::default.file_upload.sources.tabs.webcam'),
        };
    }

    public function getUploadSourceTabIcon(FileUploadSource $source): string
    {
        return match ($source) {
            FileUploadSource::File => GravityIcon::CloudArrowUpIn,
            FileUploadSource::Url => GravityIcon::Globe,
            FileUploadSource::Webcam => GravityIcon::Camera,
        };
    }

    public function supportsWebcamCapture(): bool
    {
        $accepted = $this->getAcceptedFileTypes();

        if ($accepted === null || $accepted === []) {
            return true;
        }

        foreach ($accepted as $mimeType) {
            if ($mimeType === 'image/*' || str_starts_with(strtolower($mimeType), 'image/')) {
                return true;
            }
        }

        return false;
    }

    public function acceptsOnlyImages(): bool
    {
        $accepted = $this->getAcceptedFileTypes();

        if ($accepted === null || $accepted === []) {
            return false;
        }

        foreach ($accepted as $mimeType) {
            $mimeType = strtolower($mimeType);

            if ($mimeType !== 'image/*' && ! str_starts_with($mimeType, 'image/')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<array{name: string, size: int, type: string, url: string, openableUrl?: string, downloadableUrl?: string} | null> | null
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getUploadedFiles(): ?array
    {
        $urls = parent::getUploadedFiles() ?? [];

        foreach ($this->getRawState() ?? [] as $fileKey => $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            if (($urls[$fileKey] ?? null) !== null) {
                continue;
            }

            $urls[$fileKey] = $this->formatTemporaryUploadedFileForClient($file);
        }

        return $urls;
    }

    /**
     * @return array{name: string, type: string, previewUrl: string, stagingFilename: string}
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function importUploadedFileFromUrl(string $url): array
    {
        if (! $this->shouldAllowUrlUpload()) {
            throw ValidationException::withMessages([
                $this->getStatePath() => __('filament-flex-fields::default.file_upload.sources.url_disabled'),
            ]);
        }

        $url = trim($url);

        if ($url === '') {
            throw ValidationException::withMessages([
                $this->getStatePath() => __('filament-flex-fields::default.file_upload.sources.url_required'),
            ]);
        }

        try {
            $importer = app(FileUploadRemoteImporter::class);
            $file = $importer->importFromUrl($url, $this->buildFileUploadImportConstraints());
        } catch (FileUploadImportException $exception) {
            throw ValidationException::withMessages([
                $this->getStatePath() => $exception->getMessage(),
            ]);
        }

        return $this->formatAlternateSourceStagingPayload($file);
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function commitAlternateSourceStagingUpload(string $stagingFilename): void
    {
        if (blank($stagingFilename)) {
            throw ValidationException::withMessages([
                $this->getStatePath() => __('filament-flex-fields::default.file_upload.sources.url_sync_failed'),
            ]);
        }

        $file = TemporaryUploadedFile::createFromLivewire($stagingFilename);

        $this->mergeTemporaryUpload($file);
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function discardAlternateSourceStagingUpload(string $stagingFilename): void
    {
        if (blank($stagingFilename)) {
            return;
        }

        $file = TemporaryUploadedFile::createFromLivewire($stagingFilename);

        rescue(fn () => $file->delete(), report: false);
    }

    /**
     * @return array{name: string, type: string, previewUrl: string, stagingFilename: string}
     */
    protected function formatAlternateSourceStagingPayload(TemporaryUploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $name = filled($originalName) && mb_check_encoding($originalName, 'UTF-8')
            ? $originalName
            : $file->getFilename();

        return [
            'name' => $name,
            'type' => $file->getMimeType(),
            'previewUrl' => $this->resolveTemporaryUploadedFilePreviewUrl($file),
            'stagingFilename' => $file->getFilename(),
        ];
    }

    /**
     * @return array{name: string, size: int, type: string, url: string}
     */
    protected function formatTemporaryUploadedFileForClient(TemporaryUploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $name = filled($originalName) && mb_check_encoding($originalName, 'UTF-8')
            ? $originalName
            : $file->getFilename();

        return [
            'name' => $name,
            'size' => $file->getSize(),
            'type' => $file->getMimeType(),
            'url' => $this->resolveTemporaryUploadedFilePreviewUrl($file),
        ];
    }

    protected function resolveTemporaryUploadedFilePreviewUrl(TemporaryUploadedFile $file): string
    {
        if ($file->isPreviewable()) {
            return $file->temporaryUrl();
        }

        return URL::temporarySignedRoute(
            'livewire.preview-file',
            now()->addMinutes(30)->endOfHour(),
            ['filename' => $file->getFilename()],
            absolute: false,
        );
    }

    protected function buildFileUploadImportConstraints(): FileUploadImportConstraints
    {
        $accepted = $this->getAcceptedFileTypes();

        return new FileUploadImportConstraints(
            acceptedMimeTypes: is_array($accepted) ? array_values($accepted) : null,
            allowedExtensions: $this->getAllowedExtensions(),
            maxSizeKb: $this->getMaxSize(),
            rejectExecutables: $this->shouldRejectExecutableFiles(),
            imagesOnly: $this->acceptsOnlyImages(),
        );
    }

    protected function mergeTemporaryUpload(TemporaryUploadedFile $file): void
    {
        if ($this->isDisabled()) {
            throw ValidationException::withMessages([
                $this->getStatePath() => __('filament-flex-fields::default.file_upload.sources.disabled'),
            ]);
        }

        $fileKey = (string) Str::uuid();

        if ($this->isMultiple()) {
            $state = is_array($this->getState()) ? $this->getState() : [];
            $maxFiles = $this->getMaxFiles();

            if ($maxFiles !== null && count($state) >= $maxFiles) {
                throw ValidationException::withMessages([
                    $this->getStatePath() => __('filament-flex-fields::default.file_upload.sources.max_files_reached'),
                ]);
            }

            $state[$fileKey] = $file;
            $this->state($state);

            return;
        }

        $this->state([$fileKey => $file]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUploadSourceAlpineConfiguration(): array
    {
        return [
            'hasUploadSourceTabs' => $this->hasUploadSourceTabs(),
            'uploadSourceTabKeys' => $this->getUploadSourceTabKeys(),
            'defaultUploadSource' => FileUploadSource::File->value,
            'allowUrlUpload' => $this->shouldAllowUrlUpload(),
            'allowWebcamUpload' => $this->shouldAllowWebcamUpload(),
            'uploadSourceTabsVariant' => $this->getUploadSourceTabsVariant(),
            'uploadSourceTabsColor' => $this->getUploadSourceTabsColor(),
            'schemaComponentKey' => $this->getKey(),
            'statePath' => $this->getStatePath(),
            'isMultiple' => $this->isMultiple(),
            'isPreviewable' => $this->isPreviewable(),
            'shouldAppendFiles' => $this->shouldAppendFiles(),
            'isDisabled' => $this->isDisabled(),
            'webcamFacingMode' => $this->isAvatar() ? 'user' : 'environment',
            'webcamModalId' => 'fff-webcam-upload-'.$this->getId(),
            'labels' => [
                'urlPlaceholder' => __('filament-flex-fields::default.file_upload.sources.url_placeholder'),
                'urlOpen' => __('filament-flex-fields::default.file_upload.sources.url_open'),
                'urlImport' => __('filament-flex-fields::default.file_upload.sources.url_import'),
                'urlImporting' => __('filament-flex-fields::default.file_upload.sources.url_importing'),
                'urlRequired' => __('filament-flex-fields::default.file_upload.sources.url_required'),
                'urlFetchFailed' => __('filament-flex-fields::default.file_upload.sources.url_fetch_failed'),
                'urlSyncFailed' => __('filament-flex-fields::default.file_upload.sources.url_sync_failed'),
                'webcamCapture' => __('filament-flex-fields::default.file_upload.sources.webcam_capture'),
                'webcamStarting' => __('filament-flex-fields::default.file_upload.sources.webcam_starting'),
                'webcamUnavailable' => __('filament-flex-fields::default.file_upload.sources.webcam_unavailable'),
                'webcamPermissionDenied' => __('filament-flex-fields::default.file_upload.sources.webcam_permission_denied'),
                'webcamFlipCamera' => __('filament-flex-fields::default.file_upload.sources.webcam_flip_camera'),
                'webcamFlashOn' => __('filament-flex-fields::default.file_upload.sources.webcam_flash_on'),
                'webcamFlashOff' => __('filament-flex-fields::default.file_upload.sources.webcam_flash_off'),
                'webcamOpen' => __('filament-flex-fields::default.file_upload.sources.webcam_open'),
                'webcamOpenAction' => __('filament-flex-fields::default.file_upload.sources.webcam_open_action'),
                'webcamRetake' => __('filament-flex-fields::default.file_upload.sources.webcam_retake'),
                'webcamConfirm' => __('filament-flex-fields::default.file_upload.sources.webcam_confirm'),
                'webcamConfirming' => __('filament-flex-fields::default.file_upload.sources.webcam_confirming'),
                'webcamRemove' => __('filament-flex-fields::default.file_upload.sources.webcam_remove'),
            ],
        ];
    }
}
