<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Policies;

use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Enterprise default: requires a named Gate ability (configurable).
 *
 * Register in your app, e.g. Gate::define('manageFlexFieldSchemas', fn (User $user) => $user->isAdmin());
 * or bind a custom policy via Gate::policy(FlexFieldGroup::class, YourPolicy::class).
 */
class FlexFieldGroupPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function view(?Authenticatable $user, FlexFieldGroup $group): bool
    {
        return $this->allows($user);
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function update(?Authenticatable $user, FlexFieldGroup $group): bool
    {
        return $this->allows($user);
    }

    public function delete(?Authenticatable $user, FlexFieldGroup $group): bool
    {
        return $this->allows($user);
    }

    public function deleteAny(?Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function publish(?Authenticatable $user, FlexFieldGroup $group): bool
    {
        return $this->allows($user);
    }

    public function rollback(?Authenticatable $user, FlexFieldGroup $group): bool
    {
        return $this->allows($user);
    }

    protected function allows(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        $ability = FlexFieldsConfig::getSchemaPolicyAbility();

        return Gate::forUser($user)->allows($ability);
    }
}
