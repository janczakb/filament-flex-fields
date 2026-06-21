<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\FlexFileUpload;

use Bjanczak\FilamentFlexFields\Support\Translations;
use Closure;
use Filament\Forms\Components\BaseFileUpload;

/**
 * @mixin BaseFileUpload
 */
trait FlexFileUploadDisplay
{
    protected string|Closure $flexFileUploadVariant = 'primary';

    protected bool|Closure $flexRemainingSlotsLabel = false;

    protected bool|Closure $flexUploadSummary = false;

    protected string|Closure|null $flexEmptyStateHint = null;

    protected string|Closure|null $flexDropzoneLabel = null;

    protected bool|Closure $flexRequireReplaceConfirmation = false;

    protected bool|Closure $flexCompactList = false;

    protected bool|Closure $flexShowFileIcon = false;

    public function remainingSlotsLabel(bool|Closure $condition = true): static
    {
        $this->flexRemainingSlotsLabel = $condition;

        return $this;
    }

    public function uploadSummary(bool|Closure $condition = true): static
    {
        $this->flexUploadSummary = $condition;

        return $this;
    }

    public function emptyStateHint(string|Closure $hint): static
    {
        $this->flexEmptyStateHint = $hint;

        return $this;
    }

    public function dropzoneLabel(string|Closure $label): static
    {
        $this->flexDropzoneLabel = $label;

        return $this;
    }

    public function requireReplaceConfirmation(bool|Closure $condition = true): static
    {
        $this->flexRequireReplaceConfirmation = $condition;

        return $this;
    }

    public function compactList(bool|Closure $condition = true): static
    {
        $this->flexCompactList = $condition;

        if ((bool) $this->evaluate($condition)) {
            $this->panelLayout('compact');
        }

        return $this;
    }

    public function showFileIcon(bool|Closure $condition = true): static
    {
        $this->flexShowFileIcon = $condition;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->flexFileUploadVariant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        return (string) $this->evaluate($this->flexFileUploadVariant);
    }

    public function shouldShowRemainingSlotsLabel(): bool
    {
        return (bool) $this->evaluate($this->flexRemainingSlotsLabel);
    }

    public function getRemainingSlotsLabel(): ?string
    {
        if (! $this->shouldShowRemainingSlotsLabel()) {
            return null;
        }

        $maxFiles = $this->getMaxFiles();

        if ($maxFiles === null) {
            return null;
        }

        $currentCount = count($this->normalizeFilePaths($this->getRawState()));
        $remaining = max($maxFiles - $currentCount, 0);

        return Translations::get('filament-flex-fields::default.file_upload.remaining_slots', [
            'remaining' => $remaining,
            'max' => $maxFiles,
        ]);
    }

    public function shouldShowUploadSummary(): bool
    {
        return (bool) $this->evaluate($this->flexUploadSummary);
    }

    public function getEmptyStateHint(): ?string
    {
        $hint = $this->evaluate($this->flexEmptyStateHint);

        return filled($hint) ? (string) $hint : null;
    }

    public function getDropzoneLabel(): ?string
    {
        $label = $this->evaluate($this->flexDropzoneLabel);

        return filled($label) ? (string) $label : null;
    }

    public function getEffectivePlaceholder(): ?string
    {
        return $this->getDropzoneLabel()
            ?? $this->getEmptyStateHint()
            ?? $this->getPlaceholder();
    }

    public function shouldRequireReplaceConfirmation(): bool
    {
        return (bool) $this->evaluate($this->flexRequireReplaceConfirmation);
    }

    public function shouldShowFileIcon(): bool
    {
        return (bool) $this->evaluate($this->flexShowFileIcon);
    }

    public function shouldUseCompactList(): bool
    {
        return (bool) $this->evaluate($this->flexCompactList);
    }

    /**
     * @return array<string, mixed>
     */
    public function getFlexFileUploadAlpineConfiguration(): array
    {
        return [
            ...$this->getUploadSourceAlpineConfiguration(),
            'showUploadSummary' => $this->shouldShowUploadSummary(),
            'requireReplaceConfirmation' => $this->shouldRequireReplaceConfirmation(),
            'replaceConfirmationMessage' => Translations::get('filament-flex-fields::default.file_upload.replace_confirmation'),
            'summaryTemplate' => Translations::get('filament-flex-fields::default.file_upload.summary'),
            'remainingSlotsTemplate' => $this->shouldShowRemainingSlotsLabel() && $this->getMaxFiles() !== null
                ? Translations::get('filament-flex-fields::default.file_upload.remaining_slots')
                : null,
            'maxFiles' => $this->getMaxFiles(),
            'showFileIcon' => $this->shouldShowFileIcon(),
        ];
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-flex-file-upload',
            'fff-flex-file-upload--'.$this->getSize(),
            'fff-flex-file-upload--'.$this->getVariant(),
        ];

        if ($this->shouldShowFileIcon()) {
            $classes[] = 'fff-flex-file-upload--show-file-icon';
        }

        if ($this->shouldUseCompactList()) {
            $classes[] = 'fff-flex-file-upload--compact-list';
        }

        if ($this->shouldShowFocusOutline()) {
            $classes[] = 'has-focus-outline';
        }

        if ($this->isAvatar()) {
            $classes[] = 'fff-flex-file-upload--avatar';
        }

        if ($this->hasUploadSourceTabs()) {
            $classes[] = 'fff-flex-file-upload--source-tabs';
        }

        return $classes;
    }
}
