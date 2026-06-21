<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;

use Illuminate\Database\Eloquent\Model;

/**
 * Request-scoped caches for a single UserSelect field instance.
 */
class UserSelectRuntimeState
{
    /**
     * @var array<string, array<int|string, array<string, mixed>>>
     */
    public array $searchResultsCache = [];

    /**
     * @var array<string, array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     * }>
     */
    public array $resolvedOptionShapeCache = [];

    /**
     * @var array<string, Model>
     */
    public array $resolvedRecordCache = [];

    /**
     * @var ?list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>
     */
    public ?array $cachedSelectedUsersForDisplay = null;
}
