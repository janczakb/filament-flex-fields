<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Closure;

trait InteractsWithFlexRichEditorYoutube
{
    protected bool|Closure $flexRichEditorYoutube = false;

    protected bool|Closure $flexRichEditorYoutubeNocookie = true;

    protected int|Closure $flexRichEditorYoutubeWidth = 640;

    protected int|Closure $flexRichEditorYoutubeHeight = 480;

    public function youtube(bool|Closure $condition = true): static
    {
        $this->flexRichEditorYoutube = $condition;

        return $this;
    }

    public function youtubeNocookie(bool|Closure $condition = true): static
    {
        $this->flexRichEditorYoutubeNocookie = $condition;

        return $this;
    }

    public function youtubeWidth(int|Closure $width = 640): static
    {
        $this->flexRichEditorYoutubeWidth = $width;

        return $this;
    }

    public function youtubeHeight(int|Closure $height = 480): static
    {
        $this->flexRichEditorYoutubeHeight = $height;

        return $this;
    }

    public function shouldEnableRichEditorYoutube(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorYoutube);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getYoutubeConfigForJs(): ?array
    {
        if (! $this->shouldEnableRichEditorYoutube()) {
            return null;
        }

        return [
            'width' => $this->getRichEditorYoutubeWidth(),
            'height' => $this->getRichEditorYoutubeHeight(),
            'nocookie' => $this->shouldUseRichEditorYoutubeNocookie(),
            'allowFullscreen' => true,
            'controls' => true,
            'addPasteHandler' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getYoutubeExtensionOptions(): array
    {
        return [
            'nocookie' => $this->shouldUseRichEditorYoutubeNocookie(),
            'width' => $this->getRichEditorYoutubeWidth(),
            'height' => $this->getRichEditorYoutubeHeight(),
            'allowFullscreen' => true,
            'controls' => true,
            'HTMLAttributes' => [
                'class' => 'fff-rich-editor__youtube-iframe',
            ],
        ];
    }

    public function getRichEditorYoutubeWidth(): int
    {
        $value = $this->evaluate($this->flexRichEditorYoutubeWidth);

        return is_int($value) && $value > 0 ? $value : 640;
    }

    public function getRichEditorYoutubeHeight(): int
    {
        $value = $this->evaluate($this->flexRichEditorYoutubeHeight);

        return is_int($value) && $value > 0 ? $value : 480;
    }

    public function shouldUseRichEditorYoutubeNocookie(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorYoutubeNocookie);
    }
}
