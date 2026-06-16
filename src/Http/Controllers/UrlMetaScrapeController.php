<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Http\Controllers;

use Bjanczak\FilamentFlexFields\Support\UrlMeta\UrlMetaScraper;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class UrlMetaScrapeController extends Controller
{
    public function scrape(Request $request, UrlMetaScraper $scraper): JsonResponse
    {
        $this->ensureWithinRateLimit($request);

        $url = $request->query('url');

        if (! is_string($url) || $url === '' || ! $scraper->isScrapableUrl($url)) {
            return response()->json([
                'error' => __('filament-flex-fields::default.link_preview.invalid_url'),
            ], 400);
        }

        return response()->json($scraper->scrape($url));
    }

    protected function ensureWithinRateLimit(Request $request): void
    {
        $maxAttempts = (int) config('filament-flex-fields.link_preview.rate_limit_per_minute', 30);

        if ($maxAttempts <= 0) {
            return;
        }

        try {
            $key = 'fff-url-meta:'.($request->user()?->getAuthIdentifier() ?? $request->ip());

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                throw ValidationException::withMessages([
                    'url' => [__('filament-flex-fields::default.link_preview.rate_limited')],
                ]);
            }

            RateLimiter::hit($key, 60);
        } catch (QueryException $exception) {
            report($exception);
        }
    }
}
