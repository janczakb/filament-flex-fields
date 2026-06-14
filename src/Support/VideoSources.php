<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class VideoSources
{
    public const PROVIDER_HTML5 = 'html5';

    public const PROVIDER_YOUTUBE = 'youtube';

    public const PROVIDER_VIMEO = 'vimeo';

    public static function vimeoId(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $url = trim($url);

        if (preg_match('~(?:vimeo\.com/|player\.vimeo\.com/video/)([0-9]+)~', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function isVimeo(?string $url): bool
    {
        return self::vimeoId($url) !== null;
    }

    public static function vimeoEmbedUrl(
        string $videoId,
        bool $autoplay = false,
        bool $loop = false,
        bool $muted = false,
    ): string {
        $base = 'https://player.vimeo.com/video/';

        $params = array_filter([
            'autoplay' => $autoplay ? 1 : null,
            'loop' => $loop ? 1 : null,
            'muted' => $muted ? 1 : null,
        ], fn (mixed $value): bool => $value !== null);

        $query = http_build_query($params);

        return $base.$videoId.($query !== '' ? '?'.$query : '');
    }

    public static function youtubeId(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $url = trim($url);

        if (preg_match('~(?:youtube\.com/watch\?(?:[^&\s]+&)*v=|youtube\.com/embed/|youtube\.com/shorts/|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public static function isYoutube(?string $url): bool
    {
        return self::youtubeId($url) !== null;
    }

    public static function youtubeThumbnail(string $videoId, string $quality = 'hqdefault'): string
    {
        return sprintf('https://i.ytimg.com/vi/%s/%s.jpg', $videoId, $quality);
    }

    /**
     * @return array<string, int|string>
     */
    public static function youtubeIframePlayerVars(
        bool $autoplay = false,
        bool $loop = false,
        bool $muted = false,
        bool $hideYoutubeControls = true,
    ): array {
        $vars = [
            'autoplay' => $autoplay ? 1 : 0,
            'controls' => $hideYoutubeControls ? 0 : 1,
            'disablekb' => $hideYoutubeControls ? 1 : 0,
            'fs' => 0,
            'modestbranding' => 1,
            'rel' => 0,
            'playsinline' => 1,
            'enablejsapi' => 1,
            'iv_load_policy' => 3,
            'cc_load_policy' => 0,
        ];

        if ($loop) {
            $vars['loop'] = 1;
        }

        if ($muted) {
            $vars['mute'] = 1;
        }

        return $vars;
    }

    public static function youtubeEmbedUrl(
        string $videoId,
        bool $autoplay = false,
        bool $noCookie = true,
        bool $showNativeControls = true,
    ): string {
        $base = $noCookie
            ? 'https://www.youtube-nocookie.com/embed/'
            : 'https://www.youtube.com/embed/';

        $params = array_filter([
            'autoplay' => $autoplay ? 1 : null,
            'controls' => $showNativeControls ? 1 : null,
            'rel' => 0,
            'modestbranding' => 1,
            'playsinline' => 1,
        ], fn (mixed $value): bool => $value !== null);

        $query = http_build_query($params);

        return $base.$videoId.($query !== '' ? '?'.$query : '');
    }

    public static function resolveProvider(?string $url, bool $allowYoutube = true, bool $allowVimeo = true): string
    {
        if ($allowYoutube && self::isYoutube($url)) {
            return self::PROVIDER_YOUTUBE;
        }

        if ($allowVimeo && self::isVimeo($url)) {
            return self::PROVIDER_VIMEO;
        }

        return filled($url) ? self::PROVIDER_HTML5 : '';
    }
}
