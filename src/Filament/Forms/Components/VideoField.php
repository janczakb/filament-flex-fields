<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\ResolvesConfiguredIcons;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Security\SafeMediaUrl;
use Bjanczak\FilamentFlexFields\Support\VideoSources;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class VideoField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use ResolvesConfiguredIcons;

    protected string $view = 'filament-flex-fields::forms.components.video-field';

    protected string|Closure|null $src = null;

    protected string|Closure|null $poster = null;

    protected string|Closure|null $title = null;

    protected string|Closure|null $subtitle = null;

    protected string|Closure|null $ratio = '16:9';

    protected bool|Closure $isFullWidth = false;

    protected bool|Closure $showControls = true;

    protected bool|Closure $nativeControls = false;

    protected bool|Closure $autoplay = false;

    protected bool|Closure $loop = false;

    protected bool|Closure $muted = false;

    protected bool|Closure $playsInline = true;

    protected int|Closure $skipSeconds = 10;

    protected bool|Closure $fullscreenable = true;

    protected bool|Closure $autoHideControls = true;

    protected bool|Closure $pictureInPictureable = false;

    protected bool|Closure $volumeControl = true;

    protected bool|Closure $allowYoutube = true;

    protected bool|Closure $allowVimeo = true;

    protected bool|Closure $youtubeNoCookie = true;

    protected string|Closure $controlsLayout = 'default';

    protected string|BackedEnum|Htmlable|Closure|null $playIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $pauseIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $volumeIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $muteIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $fullscreenIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $exitFullscreenIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $pictureInPictureIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $exitPictureInPictureIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $placeholderIcon = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);

        $this->rules(['nullable', 'string']);

        $this->rule(function (VideoField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                if (! is_string($value)) {
                    $fail(__('filament-flex-fields::default.validation.media.invalid_url'));

                    return;
                }

                if (SafeMediaUrl::sanitize($value) === null) {
                    $fail(__('filament-flex-fields::default.validation.media.invalid_url'));
                }
            };
        });

        $defaultLayout = config('filament-flex-fields.ui.video_controls_layout');

        if (is_string($defaultLayout) && in_array($defaultLayout, ['default', 'compact'], true)) {
            $this->controlsLayout = $defaultLayout;
        }
    }

    public function src(string|Closure|null $src): static
    {
        $this->src = $src;

        return $this;
    }

    public function poster(string|Closure|null $poster): static
    {
        $this->poster = $poster;

        return $this;
    }

    public function placeholder(string|Closure|null $poster): static
    {
        return $this->poster($poster);
    }

    public function title(string|Closure|null $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function subtitle(string|Closure|null $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function ratio(string|Closure|null $ratio): static
    {
        $this->ratio = $ratio;

        return $this;
    }

    public function fullWidth(bool|Closure $condition = true): static
    {
        $this->isFullWidth = $condition;

        return $this;
    }

    public function controls(bool|Closure $condition = true): static
    {
        $this->showControls = $condition;

        return $this;
    }

    public function nativeControls(bool|Closure $condition = true): static
    {
        $this->nativeControls = $condition;

        return $this;
    }

    public function autoplay(bool|Closure $condition = true): static
    {
        $this->autoplay = $condition;

        return $this;
    }

    public function loop(bool|Closure $condition = true): static
    {
        $this->loop = $condition;

        return $this;
    }

    public function muted(bool|Closure $condition = true): static
    {
        $this->muted = $condition;

        return $this;
    }

    public function playsInline(bool|Closure $condition = true): static
    {
        $this->playsInline = $condition;

        return $this;
    }

    public function skipSeconds(int|Closure $seconds): static
    {
        if ($seconds instanceof Closure) {
            $this->skipSeconds = $seconds;

            return $this;
        }

        if ($seconds < 1) {
            throw new InvalidArgumentException('Video skip seconds must be at least 1.');
        }

        $this->skipSeconds = $seconds;

        return $this;
    }

    public function fullscreenable(bool|Closure $condition = true): static
    {
        $this->fullscreenable = $condition;

        return $this;
    }

    public function autoHideControls(bool|Closure $condition = true): static
    {
        $this->autoHideControls = $condition;

        return $this;
    }

    public function pictureInPictureable(bool|Closure $condition = true): static
    {
        $this->pictureInPictureable = $condition;

        return $this;
    }

    public function volumeControl(bool|Closure $condition = true): static
    {
        $this->volumeControl = $condition;

        return $this;
    }

    public function allowYoutube(bool|Closure $condition = true): static
    {
        $this->allowYoutube = $condition;

        return $this;
    }

    public function allowVimeo(bool|Closure $condition = true): static
    {
        $this->allowVimeo = $condition;

        return $this;
    }

    public function youtubeNoCookie(bool|Closure $condition = true): static
    {
        $this->youtubeNoCookie = $condition;

        return $this;
    }

    public function controlsLayout(string|Closure $layout): static
    {
        $this->controlsLayout = $layout;

        return $this;
    }

    public function compactControls(bool|Closure $condition = true): static
    {
        $this->controlsLayout = static function (VideoField $component) use ($condition): string {
            return $component->evaluate($condition) ? 'compact' : 'default';
        };

        return $this;
    }

    public function getControlsLayout(): string
    {
        $layout = (string) $this->evaluate($this->controlsLayout);

        if (! in_array($layout, ['default', 'compact'], true)) {
            throw new InvalidArgumentException("Video controls layout [{$layout}] is not supported.");
        }

        return $layout;
    }

    public function usesCompactControls(): bool
    {
        return $this->getControlsLayout() === 'compact';
    }

    public function playIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->playIcon = $icon;

        return $this;
    }

    public function pauseIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->pauseIcon = $icon;

        return $this;
    }

    public function volumeIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->volumeIcon = $icon;

        return $this;
    }

    public function muteIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->muteIcon = $icon;

        return $this;
    }

    public function fullscreenIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->fullscreenIcon = $icon;

        return $this;
    }

    public function exitFullscreenIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->exitFullscreenIcon = $icon;

        return $this;
    }

    public function pictureInPictureIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->pictureInPictureIcon = $icon;

        return $this;
    }

    public function exitPictureInPictureIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->exitPictureInPictureIcon = $icon;

        return $this;
    }

    public function placeholderIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->placeholderIcon = $icon;

        return $this;
    }

    public function getPlayIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->playIcon, 'video_play_icon', GravityIcon::PlayFill);
    }

    public function getPauseIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->pauseIcon, 'video_pause_icon', GravityIcon::PauseFill);
    }

    public function getVolumeIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->volumeIcon, 'video_volume_icon', GravityIcon::VolumeFill);
    }

    public function getMuteIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->muteIcon, 'video_mute_icon', GravityIcon::VolumeSlashFill);
    }

    public function getFullscreenIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->fullscreenIcon, 'video_fullscreen_icon', GravityIcon::ChevronsExpandUpRight);
    }

    public function getExitFullscreenIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->exitFullscreenIcon, 'video_exit_fullscreen_icon', GravityIcon::ChevronsCollapseUpRight);
    }

    public function getPictureInPictureIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->pictureInPictureIcon, 'video_picture_in_picture_icon', GravityIcon::CopyPicture);
    }

    public function getExitPictureInPictureIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->exitPictureInPictureIcon, 'video_exit_picture_in_picture_icon', GravityIcon::ChevronsCollapseUpRight);
    }

    public function getPlaceholderIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->placeholderIcon, 'video_placeholder_icon', GravityIcon::Video);
    }

    public function getSrc(): ?string
    {
        $src = $this->evaluate($this->src);

        if (! filled($src)) {
            return null;
        }

        return SafeMediaUrl::sanitize((string) $src);
    }

    public function getPoster(): ?string
    {
        $poster = $this->evaluate($this->poster);

        if (! filled($poster)) {
            return null;
        }

        return SafeMediaUrl::sanitize((string) $poster);
    }

    public function getTitle(): ?string
    {
        $title = $this->evaluate($this->title);

        return filled($title) ? (string) $title : null;
    }

    public function getSubtitle(): ?string
    {
        $subtitle = $this->evaluate($this->subtitle);

        return filled($subtitle) ? (string) $subtitle : null;
    }

    public function getRatio(): ?string
    {
        $ratio = $this->evaluate($this->ratio);

        if ($ratio === null || $ratio === '' || $ratio === 'auto') {
            return null;
        }

        return (string) $ratio;
    }

    public function getAspectRatioStyle(): ?string
    {
        $ratio = $this->getRatio();

        if ($ratio === null) {
            return null;
        }

        if (str_contains($ratio, ':')) {
            [$width, $height] = array_pad(explode(':', $ratio, 2), 2, null);

            if (! is_numeric($width) || ! is_numeric($height) || (float) $height === 0.0) {
                throw new InvalidArgumentException("Invalid video ratio [{$ratio}]. Use formats like 16:9 or 4:3.");
            }

            return "{$width} / {$height}";
        }

        if (str_contains($ratio, '/')) {
            return $ratio;
        }

        if (is_numeric($ratio) && (float) $ratio > 0) {
            return (string) $ratio;
        }

        throw new InvalidArgumentException("Invalid video ratio [{$ratio}]. Use formats like 16:9 or 4:3.");
    }

    public function isFullWidth(): bool
    {
        return (bool) $this->evaluate($this->isFullWidth);
    }

    public function shouldShowControls(): bool
    {
        return (bool) $this->evaluate($this->showControls);
    }

    public function shouldUseNativeControls(): bool
    {
        return (bool) $this->evaluate($this->nativeControls);
    }

    public function shouldAutoplay(): bool
    {
        return (bool) $this->evaluate($this->autoplay);
    }

    public function shouldLoop(): bool
    {
        return (bool) $this->evaluate($this->loop);
    }

    public function shouldStartMuted(): bool
    {
        return (bool) $this->evaluate($this->muted);
    }

    public function shouldPlayInline(): bool
    {
        return (bool) $this->evaluate($this->playsInline);
    }

    public function getSkipSeconds(): int
    {
        return max(1, (int) $this->evaluate($this->skipSeconds));
    }

    public function isFullscreenable(): bool
    {
        return (bool) $this->evaluate($this->fullscreenable);
    }

    public function autoHidesControls(): bool
    {
        return (bool) $this->evaluate($this->autoHideControls);
    }

    public function isPictureInPictureable(): bool
    {
        return (bool) $this->evaluate($this->pictureInPictureable);
    }

    public function supportsPictureInPicture(mixed $state = null): bool
    {
        if (! $this->isPictureInPictureable()) {
            return false;
        }

        if ($this->usesYoutubeEmbed($state)) {
            return false;
        }

        return filled($this->resolveVideoSrc($state));
    }

    public function hasVolumeControl(): bool
    {
        return (bool) $this->evaluate($this->volumeControl);
    }

    public function allowsYoutube(): bool
    {
        return (bool) $this->evaluate($this->allowYoutube);
    }

    public function allowsVimeo(): bool
    {
        return (bool) $this->evaluate($this->allowVimeo);
    }

    public function shouldUseYoutubeNoCookie(): bool
    {
        return (bool) $this->evaluate($this->youtubeNoCookie);
    }

    public function hasMetadata(): bool
    {
        return filled($this->getTitle()) || filled($this->getSubtitle());
    }

    public function resolveVideoSrc(mixed $state): ?string
    {
        $staticSrc = $this->getSrc();

        if ($staticSrc !== null) {
            return $staticSrc;
        }

        if (is_string($state) && filled($state)) {
            return SafeMediaUrl::sanitize($state);
        }

        return null;
    }

    public function resolveYoutubeId(mixed $state): ?string
    {
        if (! $this->allowsYoutube()) {
            return null;
        }

        return VideoSources::youtubeId($this->resolveVideoSrc($state));
    }

    public function usesYoutubeEmbed(mixed $state): bool
    {
        return $this->resolveYoutubeId($state) !== null;
    }

    public function resolveProvider(mixed $state): string
    {
        return VideoSources::resolveProvider(
            $this->resolveVideoSrc($state),
            $this->allowsYoutube(),
            $this->allowsVimeo(),
        );
    }

    public function resolveYoutubeEmbedUrl(mixed $state, bool $autoplay = false): ?string
    {
        $videoId = $this->resolveYoutubeId($state);

        if ($videoId === null) {
            return null;
        }

        return VideoSources::youtubeEmbedUrl(
            $videoId,
            $autoplay,
            $this->shouldUseYoutubeNoCookie(),
            $this->shouldUseNativeControls() || ! $this->shouldShowControls(),
        );
    }

    public function resolveVimeoId(mixed $state): ?string
    {
        if (! $this->allowsVimeo()) {
            return null;
        }

        return VideoSources::vimeoId($this->resolveVideoSrc($state));
    }

    public function usesVimeoEmbed(mixed $state): bool
    {
        return $this->resolveVimeoId($state) !== null;
    }

    public function resolveVimeoEmbedUrl(mixed $state, bool $autoplay = false): ?string
    {
        $videoId = $this->resolveVimeoId($state);

        if ($videoId === null) {
            return null;
        }

        return VideoSources::vimeoEmbedUrl(
            $videoId,
            $autoplay,
            $this->shouldLoop(),
            $this->shouldStartMuted(),
        );
    }

    public function usesYoutubeCustomControls(mixed $state): bool
    {
        return $this->usesYoutubeEmbed($state)
            && $this->shouldShowControls()
            && ! $this->shouldUseNativeControls();
    }

    public function usesYoutubeFacade(mixed $state): bool
    {
        return $this->usesYoutubeEmbed($state) && ! $this->usesYoutubeCustomControls($state);
    }

    /**
     * @return array<string, int|string>
     */
    public function getYoutubeIframePlayerVars(mixed $state, bool $autoplay = false): array
    {
        $videoId = $this->resolveYoutubeId($state);

        $vars = VideoSources::youtubeIframePlayerVars(
            $autoplay,
            $this->shouldLoop(),
            $this->shouldStartMuted(),
            hideYoutubeControls: true,
        );

        if ($this->shouldLoop() && $videoId !== null) {
            $vars['playlist'] = $videoId;
        }

        return $vars;
    }

    public function resolveYoutubeThumbnail(mixed $state): ?string
    {
        $videoId = $this->resolveYoutubeId($state);

        if ($videoId === null) {
            return null;
        }

        if ($this->getPoster() !== null) {
            return $this->getPoster();
        }

        return VideoSources::youtubeThumbnail($videoId);
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-video-field-field',
            'fff-video-field-field--'.$this->getSize(),
        ];
    }
}
