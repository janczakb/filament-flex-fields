<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Wraps multi-user column state so Filament TextColumn treats it as a single value
 * instead of comma-joining each user into separate rich layouts.
 */
final readonly class UserColumnStackState
{
    /**
     * @param  array<int, Model>|Collection<int, Model>  $users
     */
    public function __construct(
        public array|Collection $users,
    ) {}
}
