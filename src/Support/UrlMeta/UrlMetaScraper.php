<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\UrlMeta;

use Bjanczak\FilamentFlexFields\Support\Http\SafeRemoteUrlValidator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\StreamInterface;
use Throwable;

class UrlMetaScraper
{
    private const int MAX_BYTES = 131_072;

    private const int CHUNK_SIZE = 4_096;

    private const int MAX_REDIRECTS = 5;

    private const string USER_AGENT = 'Mozilla/5.0 (compatible; FilamentFlexFields/1.0; +https://github.com/janczakb/filament-flex-fields)';

    public function __construct(
        private readonly RedirectUrlResolver $redirectUrlResolver,
        private readonly SafeRemoteUrlValidator $urlValidator,
    ) {}

    public function isScrapableUrl(string $url): bool
    {
        return $this->urlValidator->isAllowedHttpUrl($url);
    }

    /**
     * @return array{title?: string, description?: string, image?: string}
     */
    public function scrape(string $url): array
    {
        if (! $this->isScrapableUrl($url)) {
            return [];
        }

        $cacheKey = 'fff_meta_'.hash('sha256', $url);
        $ttl = (int) config('filament-flex-fields.link_preview.cache_ttl_seconds', 86_400);

        try {
            return Cache::remember($cacheKey, now()->addSeconds($ttl), fn (): array => $this->scrapeUncached($url));
        } catch (QueryException) {
            return $this->scrapeUncached($url);
        }
    }

    /**
     * @return array{title?: string, description?: string, image?: string}
     */
    private function scrapeUncached(string $url): array
    {
        $html = $this->fetchHeadHtml($url);

        if ($html === null || $html === '') {
            return [];
        }

        return $this->parseFromHtml($html, $url);
    }

    /**
     * @return array{title?: string, description?: string, image?: string}
     */
    public function parseFromHtml(string $html, string $baseUrl): array
    {
        $headHtml = $this->extractHeadHtml($html);
        $meta = $this->extractMetaMap($headHtml);
        $links = $this->extractLinkMap($headHtml);
        $titleTag = $this->extractTitleTag($headHtml);

        $title = $meta['og:title']
            ?? $meta['twitter:title']
            ?? $titleTag;

        $description = $meta['description']
            ?? $meta['og:description']
            ?? $meta['twitter:description']
            ?? null;

        $image = $meta['og:image']
            ?? $meta['og:image:url']
            ?? $meta['og:image:secure_url']
            ?? $meta['twitter:image']
            ?? $meta['twitter:image:src']
            ?? $links['image_src']
            ?? $links['icon']
            ?? $links['shortcut icon']
            ?? null;

        $result = [];

        if ($normalizedTitle = $this->normalizeText($title)) {
            $result['title'] = $normalizedTitle;
        }

        if ($normalizedDescription = $this->normalizeText($description)) {
            $result['description'] = $normalizedDescription;
        }

        if ($normalizedImage = $this->normalizeImageUrl($baseUrl, $image)) {
            $result['image'] = $normalizedImage;
        }

        return $result;
    }

    private function fetchHeadHtml(string $url): ?string
    {
        $currentUrl = $url;
        $timeout = (int) config('filament-flex-fields.link_preview.timeout_seconds', 3);

        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            if (! $this->urlValidator->isAllowedHttpUrl($currentUrl)) {
                return null;
            }

            try {
                $response = Http::timeout($timeout)
                    ->withUserAgent(self::USER_AGENT)
                    ->withOptions([
                        'stream' => true,
                        'allow_redirects' => false,
                    ])
                    ->get($currentUrl);
            } catch (ConnectionException) {
                return null;
            } catch (Throwable) {
                return null;
            }

            if ($response->redirect()) {
                $location = $response->header('Location');

                if (! is_string($location) || $location === '') {
                    return null;
                }

                $currentUrl = $this->redirectUrlResolver->resolve($currentUrl, $location);

                continue;
            }

            if (! $response->successful()) {
                return null;
            }

            return $this->readUntilHeadClosed($response);
        }

        return null;
    }

    private function readUntilHeadClosed(Response $response): ?string
    {
        $stream = $response->toPsrResponse()->getBody();

        if (! $stream instanceof StreamInterface) {
            return null;
        }

        $buffer = '';
        $bytesRead = 0;

        try {
            while (! $stream->eof() && $bytesRead < self::MAX_BYTES) {
                $chunk = $stream->read(self::CHUNK_SIZE);

                if ($chunk === '') {
                    break;
                }

                $buffer .= $chunk;
                $bytesRead += strlen($chunk);

                if (stripos($buffer, '</head>') !== false) {
                    break;
                }
            }
        } finally {
            $stream->close();
        }

        return $buffer !== '' ? $buffer : null;
    }

    private function extractHeadHtml(string $html): string
    {
        if (preg_match('/<head\b[^>]*>(.*?)<\/head>/is', $html, $matches) === 1) {
            return $matches[1];
        }

        return $html;
    }

    /**
     * @return array<string, string>
     */
    private function extractMetaMap(string $headHtml): array
    {
        $map = [];

        if (preg_match_all('/<meta\s+([^>]+)>/i', $headHtml, $matches) === false) {
            return $map;
        }

        foreach ($matches[1] as $attributeString) {
            $key = $this->matchAttribute($attributeString, 'property')
                ?? $this->matchAttribute($attributeString, 'name');
            $content = $this->matchAttribute($attributeString, 'content');

            if ($key === null || $content === null || isset($map[$key])) {
                continue;
            }

            $map[$key] = $this->decodeHtml($content);
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    private function extractLinkMap(string $headHtml): array
    {
        $map = [];

        if (preg_match_all('/<link\s+([^>]+)>/i', $headHtml, $matches) === false) {
            return $map;
        }

        foreach ($matches[1] as $attributeString) {
            $rel = $this->matchAttribute($attributeString, 'rel');
            $href = $this->matchAttribute($attributeString, 'href');

            if ($rel === null || $href === null || isset($map[$rel])) {
                continue;
            }

            $map[$rel] = $this->decodeHtml($href);
        }

        return $map;
    }

    private function extractTitleTag(string $headHtml): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $headHtml, $matches) !== 1) {
            return null;
        }

        return $this->normalizeText($this->decodeHtml($matches[1]));
    }

    private function matchAttribute(string $attributeString, string $name): ?string
    {
        $pattern = '/\b'.preg_quote($name, '/').'\s*=\s*(["\'])(.*?)\1/is';

        if (preg_match($pattern, $attributeString, $matches) !== 1) {
            return null;
        }

        return $matches[2];
    }

    private function decodeHtml(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeImageUrl(string $baseUrl, ?string $image): ?string
    {
        if ($image === null || trim($image) === '') {
            return null;
        }

        $image = trim($image);

        if (Str::startsWith($image, 'data:')) {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        return $this->resolveAbsoluteUrl($baseUrl, $image);
    }

    private function resolveAbsoluteUrl(string $baseUrl, string $relativeUrl): string
    {
        if (parse_url($relativeUrl, PHP_URL_SCHEME) !== null) {
            return $relativeUrl;
        }

        $parts = parse_url($baseUrl) ?: [];
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        if (str_starts_with($relativeUrl, '//')) {
            return $scheme.':'.$relativeUrl;
        }

        if (str_starts_with($relativeUrl, '/')) {
            return $scheme.'://'.$host.$relativeUrl;
        }

        $path = $parts['path'] ?? '/';
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');

        return $scheme.'://'.$host.($directory !== '' ? $directory.'/' : '/').ltrim($relativeUrl, '/');
    }
}
