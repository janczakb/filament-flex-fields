<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Http\Controllers;

use Bjanczak\FilamentFlexFields\Support\Mapbox\MapboxGeocodingClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class MapboxGeocodingProxyController extends Controller
{
    public function search(Request $request, MapboxGeocodingClient $client): JsonResponse
    {
        $this->ensureWithinRateLimit($request);

        $validated = $request->validate([
            'query' => ['required', 'string', 'min:1', 'max:256'],
            'language' => ['nullable', 'string', 'max:16'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'autocomplete' => ['nullable', 'boolean'],
            'types' => ['nullable', 'string', 'max:128'],
            'countries' => ['nullable', 'array'],
            'countries.*' => ['string', 'size:2'],
            'street_addresses_only' => ['nullable', 'boolean'],
        ]);

        return response()->json(
            $client->search($validated),
        );
    }

    public function reverse(Request $request, MapboxGeocodingClient $client): JsonResponse
    {
        $this->ensureWithinRateLimit($request);

        $validated = $request->validate([
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'language' => ['nullable', 'string', 'max:16'],
            'types' => ['nullable', 'string', 'max:128'],
            'countries' => ['nullable', 'array'],
            'countries.*' => ['string', 'size:2'],
            'street_addresses_only' => ['nullable', 'boolean'],
        ]);

        return response()->json(
            $client->reverse($validated),
        );
    }

    protected function ensureWithinRateLimit(Request $request): void
    {
        $key = 'fff-geocode:'.($request->user()?->getAuthIdentifier() ?? $request->ip());
        $maxAttempts = (int) config('filament-flex-fields.mapbox.rate_limit_per_minute', 60);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                'geocode' => [__('filament-flex-fields::default.geocoding.rate_limited')],
            ]);
        }

        RateLimiter::hit($key, 60);
    }
}
