<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Illuminate\Support\Facades\Cache;

final class FlexFieldsPlaygroundStore
{
    /**
     * @return array<string, mixed>|null
     */
    public function get(string $slug): ?array
    {
        $stored = Cache::get($this->key($slug));

        return is_array($stored) ? $stored : null;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function put(string $slug, array $state): void
    {
        Cache::put($this->key($slug), $state, now()->addDays(30));
    }

    public function forget(string $slug): void
    {
        Cache::forget($this->key($slug));
    }

    public function key(string $slug): string
    {
        $userId = auth()->id() ?? 'guest';

        return "flex-fields-playground.{$slug}.{$userId}";
    }
}
