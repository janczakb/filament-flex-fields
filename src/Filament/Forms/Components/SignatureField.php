<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\ResolvesConfiguredIcons;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\SignatureSvg;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class SignatureField extends Field
{
    use CanBeReadOnly;
    use ResolvesConfiguredIcons;

    public const string STORE_SVG = 'svg';

    public const string DOWNLOAD_SVG = 'svg';

    public const string DOWNLOAD_WEBP = 'webp';

    protected string $view = 'filament-flex-fields::forms.components.signature-field';

    protected string|Closure $penColor = '#18181b';

    protected float|Closure $penWidth = 2.5;

    protected string|Closure|null $backgroundColor = '#ffffff';

    protected bool|Closure $fullscreen = true;

    protected bool|Closure $undoable = true;

    protected int|Closure $maxSizeKb = 48;

    protected int|Closure $minStrokes = 1;

    protected int|Closure $viewBoxWidth = SignatureSvg::VIEWBOX_WIDTH;

    protected int|Closure $viewBoxHeight = SignatureSvg::VIEWBOX_HEIGHT;

    protected bool|Closure $smoothing = true;

    protected bool|Closure $trackpadGlide = false;

    protected string|Closure $trackpadGlideKey = 'd';

    protected bool|Closure $guidelines = false;

    protected string|Closure|null $downloadFormat = null;

    protected string|Closure $downloadFilename = 'signature';

    protected float|Closure $webpQuality = 0.9;

    protected string|BackedEnum|Htmlable|Closure|null $undoIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $clearIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $downloadIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $fullscreenIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $closeIcon = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);

        $this->rule(function (SignatureField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if ($value === null || $value === '') {
                    if ($component->isRequired()) {
                        $fail(__('validation.required', ['attribute' => $component->getLabel()]));
                    }

                    return;
                }

                if (! is_string($value)) {
                    $fail(__('filament-flex-fields::default.validation.signature.invalid'));

                    return;
                }

                if (! SignatureSvg::isValid($value)) {
                    $fail(__('filament-flex-fields::default.validation.signature.invalid'));

                    return;
                }

                $maxBytes = max(1, $component->getMaxSizeKb()) * 1024;

                if (SignatureSvg::byteSize($value) > $maxBytes) {
                    $fail(__('filament-flex-fields::default.validation.signature.too_large', [
                        'max' => $component->getMaxSizeKb(),
                    ]));

                    return;
                }

                if (SignatureSvg::countPaths($value) < $component->getMinStrokes()) {
                    $fail(__('filament-flex-fields::default.validation.signature.too_few_strokes'));
                }
            };
        });
    }

    public function penColor(string|Closure $color): static
    {
        $this->penColor = $color;

        return $this;
    }

    public function penWidth(float|Closure $width): static
    {
        $this->penWidth = $width;

        return $this;
    }

    public function backgroundColor(string|Closure|null $color): static
    {
        $this->backgroundColor = $color;

        return $this;
    }

    public function fullscreen(bool|Closure $condition = true): static
    {
        $this->fullscreen = $condition;

        return $this;
    }

    public function undoable(bool|Closure $condition = true): static
    {
        $this->undoable = $condition;

        return $this;
    }

    public function maxSizeKb(int|Closure $kilobytes): static
    {
        $this->maxSizeKb = $kilobytes;

        return $this;
    }

    public function minStrokes(int|Closure $strokes): static
    {
        $this->minStrokes = $strokes;

        return $this;
    }

    public function viewBox(int|Closure $width, int|Closure $height): static
    {
        $this->viewBoxWidth = $width;
        $this->viewBoxHeight = $height;

        return $this;
    }

    public function smoothing(bool|Closure $condition = true): static
    {
        $this->smoothing = $condition;

        return $this;
    }

    public function trackpadGlide(bool|Closure $condition = true): static
    {
        $this->trackpadGlide = $condition;

        return $this;
    }

    public function trackpadGlideKey(string|Closure $key): static
    {
        $this->trackpadGlideKey = $key;

        return $this;
    }

    public function guidelines(bool|Closure $condition = true): static
    {
        $this->guidelines = $condition;

        return $this;
    }

    /**
     * @param  self::DOWNLOAD_SVG|self::DOWNLOAD_WEBP|Closure|null  $format
     */
    public function downloadable(string|Closure|null $format = self::DOWNLOAD_SVG): static
    {
        if ($format !== null && ! in_array($format, [self::DOWNLOAD_SVG, self::DOWNLOAD_WEBP], true)) {
            throw new InvalidArgumentException("Signature download format [{$format}] is not supported.");
        }

        $this->downloadFormat = $format;

        return $this;
    }

    public function downloadFilename(string|Closure $filename): static
    {
        $this->downloadFilename = $filename;

        return $this;
    }

    public function webpQuality(float|Closure $quality): static
    {
        $this->webpQuality = $quality;

        return $this;
    }

    public function undoIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->undoIcon = $icon;

        return $this;
    }

    public function clearIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->clearIcon = $icon;

        return $this;
    }

    public function downloadIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->downloadIcon = $icon;

        return $this;
    }

    public function fullscreenIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->fullscreenIcon = $icon;

        return $this;
    }

    public function closeIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->closeIcon = $icon;

        return $this;
    }

    public function getUndoIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->undoIcon, 'signature_undo_icon', GravityIcon::ArrowRotateLeft);
    }

    public function getClearIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->clearIcon, 'signature_clear_icon', GravityIcon::ArrowsRotateRight);
    }

    public function getDownloadIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->downloadIcon, 'signature_download_icon', GravityIcon::ArrowDownToSquare);
    }

    public function getFullscreenIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->fullscreenIcon, 'signature_fullscreen_icon', GravityIcon::ChevronsExpandUpRight);
    }

    public function getCloseIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->closeIcon, 'signature_close_icon', GravityIcon::Xmark);
    }

    public function getPenColor(): string
    {
        $color = (string) $this->evaluate($this->penColor);

        if (! preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
            throw new InvalidArgumentException("Signature pen color [{$color}] must be a hex color.");
        }

        return strtolower($color);
    }

    public function getPenWidth(): float
    {
        return max(0.5, min(12, (float) $this->evaluate($this->penWidth)));
    }

    public function getBackgroundColor(): ?string
    {
        $color = $this->evaluate($this->backgroundColor);

        if ($color === null || $color === 'transparent') {
            return null;
        }

        $color = (string) $color;

        if (! preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
            throw new InvalidArgumentException("Signature background color [{$color}] must be a hex color or null.");
        }

        return strtolower($color);
    }

    public function isFullscreenEnabled(): bool
    {
        return (bool) $this->evaluate($this->fullscreen);
    }

    public function isUndoable(): bool
    {
        return (bool) $this->evaluate($this->undoable);
    }

    public function getMaxSizeKb(): int
    {
        return max(1, (int) $this->evaluate($this->maxSizeKb));
    }

    public function getMinStrokes(): int
    {
        return max(1, (int) $this->evaluate($this->minStrokes));
    }

    public function getViewBoxWidth(): int
    {
        return max(100, (int) $this->evaluate($this->viewBoxWidth));
    }

    public function getViewBoxHeight(): int
    {
        return max(80, (int) $this->evaluate($this->viewBoxHeight));
    }

    public function isSmoothingEnabled(): bool
    {
        return (bool) $this->evaluate($this->smoothing);
    }

    public function isTrackpadGlideEnabled(): bool
    {
        return (bool) $this->evaluate($this->trackpadGlide);
    }

    public function getTrackpadGlideKey(): string
    {
        $key = strtolower(trim((string) $this->evaluate($this->trackpadGlideKey)));

        if (! preg_match('/^[a-z]$/', $key)) {
            throw new InvalidArgumentException("Signature trackpad glide key [{$key}] must be a single letter from a to z.");
        }

        return $key;
    }

    public function isGuidelinesEnabled(): bool
    {
        return (bool) $this->evaluate($this->guidelines);
    }

    public function getDownloadFormat(): ?string
    {
        $format = $this->evaluate($this->downloadFormat);

        return in_array($format, [self::DOWNLOAD_SVG, self::DOWNLOAD_WEBP], true) ? $format : null;
    }

    public function getDownloadFilename(): string
    {
        $filename = trim((string) $this->evaluate($this->downloadFilename));

        return $filename !== '' ? $filename : 'signature';
    }

    public function getWebpQuality(): float
    {
        return max(0.1, min(1, (float) $this->evaluate($this->webpQuality)));
    }

    public function normalizeState(mixed $state): ?string
    {
        if (! is_string($state) || trim($state) === '') {
            return null;
        }

        return SignatureSvg::normalize($state);
    }

    /**
     * @return array<string, bool>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-signature-field' => true,
        ];
    }
}
