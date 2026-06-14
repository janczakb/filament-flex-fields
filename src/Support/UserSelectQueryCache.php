<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UserSelectQueryCache
{
    /** @var array<string, Collection<int, Model>> */
    public array $cache = [];
}
