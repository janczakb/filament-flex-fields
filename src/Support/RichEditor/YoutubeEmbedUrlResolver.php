<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\RichEditor;

final class YoutubeEmbedUrlResolver
{
    public static function isValidYoutubeUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        return (bool) preg_match(
            '/^((?:https?:)?\/\/)?((?:www|m|music)\.)?((?:youtube\.com|youtu\.be|youtube-nocookie\.com))(\/(?:[\w-]+\?v=|embed\/|v\/)?)([\w-]+)(\S+)?$/',
            $url,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public static function resolve(?string $url, array $options = []): ?string
    {
        if (! self::isValidYoutubeUrl($url)) {
            return null;
        }

        if (str_contains((string) $url, '/embed/')) {
            return $url;
        }

        $urlObject = parse_url((string) $url);

        if (! is_array($urlObject)) {
            return null;
        }

        $host = $urlObject['host'] ?? '';
        $path = $urlObject['path'] ?? '';
        parse_str($urlObject['query'] ?? '', $query);

        $id = null;
        $isPlaylist = false;

        if (isset($query['v']) && is_string($query['v']) && $query['v'] !== '') {
            $id = $query['v'];
        } elseif ($host === 'youtu.be' || str_contains($path, '/shorts/') || str_contains($path, '/live/')) {
            $segments = array_values(array_filter(explode('/', trim($path, '/'))));
            $id = $segments !== [] ? (string) end($segments) : null;
        } elseif (isset($query['list']) && is_string($query['list']) && $query['list'] !== '') {
            $id = $query['list'];
            $isPlaylist = true;
        }

        if ($id === null || $id === '') {
            return null;
        }

        $nocookie = (bool) ($options['nocookie'] ?? false);
        $base = $isPlaylist
            ? 'https://www.youtube-nocookie.com/embed/videoseries?list='
            : ($nocookie ? 'https://www.youtube-nocookie.com/embed/' : 'https://www.youtube.com/embed/');

        $embedUrl = $base.$id;
        $parameters = [];

        if (isset($query['t']) && is_string($query['t']) && $query['t'] !== '') {
            $parameters['start'] = str_replace('s', '', $query['t']);
        }

        if (($options['allowFullscreen'] ?? true) === false) {
            $parameters['fs'] = '0';
        }

        if (($options['controls'] ?? true) === false) {
            $parameters['controls'] = '0';
        }

        if (($options['nocookie'] ?? false) === true && ! $isPlaylist) {
            // Already on nocookie domain.
        }

        $startAt = (int) ($options['startAt'] ?? 0);

        if ($startAt > 0) {
            $parameters['start'] = (string) $startAt;
        }

        if ($parameters === []) {
            return $embedUrl;
        }

        return $embedUrl.'?'.http_build_query($parameters);
    }
}
