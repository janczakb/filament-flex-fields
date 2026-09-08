<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Select;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate-limits Select / Tags / IconPicker Livewire search endpoints.
 *
 * Key prefers authenticated user id (Filament panel). Falls back to
 * {@see Request::ip()}, which respects Laravel TrustedProxies — never read
 * X-Forwarded-For manually (would trust spoofed headers behind a misconfigured LB).
 */
final class SelectSearchRateLimiter
{
    public function __construct(
        private readonly ?Request $request = null,
    ) {}

    public function tooManyAttempts(string $fieldName): bool
    {
        return RateLimiter::tooManyAttempts($this->key($fieldName), $this->maxAttempts());
    }

    public function hit(string $fieldName): void
    {
        RateLimiter::hit($this->key($fieldName), 60);
    }

    /**
     * @return bool true when the caller may proceed
     */
    public function attempt(string $fieldName): bool
    {
        if ($this->tooManyAttempts($fieldName)) {
            return false;
        }

        $this->hit($fieldName);

        return true;
    }

    public function key(string $fieldName): string
    {
        $request = $this->request ?? request();
        $actor = $request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'guest';
        $field = $fieldName !== '' ? $fieldName : 'field';

        return 'fff-select-search:'.$actor.':'.$field;
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('filament-flex-fields.select.search_rate_limit_per_minute', 60));
    }
}
