<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait ResolvesUserDisplay
{
    protected string|Closure $userDisplayNameColumn = 'name';

    protected string|Closure|null $userDisplayEmailColumn = null;

    protected string|Closure|null $userDisplayAvatarColumn = null;

    protected string|Closure|null $userDisplayVerificationColumn = null;

    protected ?Closure $userDisplayGetAvatarUrlUsing = null;

    protected ?Closure $userDisplayGetNameUsing = null;

    protected ?Closure $userDisplayGetEmailUsing = null;

    protected ?Closure $userDisplayIsVerifiedUsing = null;

    public function nameColumn(string|Closure $column): static
    {
        $this->userDisplayNameColumn = $column;

        return $this;
    }

    public function emailColumn(string|Closure|null $column): static
    {
        $this->userDisplayEmailColumn = $column;

        return $this;
    }

    public function avatarColumn(string|Closure|null $column): static
    {
        $this->userDisplayAvatarColumn = $column;

        return $this;
    }

    public function verificationColumn(string|Closure|null $column): static
    {
        $this->userDisplayVerificationColumn = $column;

        return $this;
    }

    public function getAvatarUrlUsing(?Closure $callback): static
    {
        $this->userDisplayGetAvatarUrlUsing = $callback;

        return $this;
    }

    public function getNameUsing(?Closure $callback): static
    {
        $this->userDisplayGetNameUsing = $callback;

        return $this;
    }

    public function getEmailUsing(?Closure $callback): static
    {
        $this->userDisplayGetEmailUsing = $callback;

        return $this;
    }

    public function isVerifiedUsing(?Closure $callback): static
    {
        $this->userDisplayIsVerifiedUsing = $callback;

        return $this;
    }

    public function getNameColumn(): string
    {
        return (string) $this->evaluate($this->userDisplayNameColumn);
    }

    public function getEmailColumn(): ?string
    {
        $column = $this->evaluate($this->userDisplayEmailColumn);

        return filled($column) ? (string) $column : null;
    }

    public function getAvatarColumn(): ?string
    {
        $column = $this->evaluate($this->userDisplayAvatarColumn);

        return filled($column) ? (string) $column : null;
    }

    public function getVerificationColumn(): ?string
    {
        $column = $this->evaluate($this->userDisplayVerificationColumn);

        return filled($column) ? (string) $column : null;
    }

    /**
     * @return array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     *     initials: string,
     * }
     */
    public function recordToDisplayArray(Model $record): array
    {
        $name = $this->resolveUserDisplayName($record);

        return [
            'label' => $name,
            'description' => $this->resolveUserDisplayEmail($record),
            'image' => $this->resolveUserDisplayAvatarUrl($record),
            'verified' => $this->resolveUserDisplayIsVerified($record),
            'initials' => $this->resolveUserDisplayInitials($name),
        ];
    }

    public function resolveUserDisplayInitials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];

        if ($parts === []) {
            return '';
        }

        if (count($parts) === 1) {
            return Str::upper(Str::substr($parts[0], 0, 2));
        }

        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= Str::upper(Str::substr($part, 0, 1));
        }

        return $initials;
    }

    protected function resolveUserDisplayName(Model $record): string
    {
        if ($this->userDisplayGetNameUsing !== null) {
            return (string) $this->evaluate($this->userDisplayGetNameUsing, ['record' => $record]);
        }

        return (string) data_get($record, $this->getNameColumn());
    }

    protected function resolveUserDisplayEmail(Model $record): ?string
    {
        if ($this->userDisplayGetEmailUsing !== null) {
            $email = $this->evaluate($this->userDisplayGetEmailUsing, ['record' => $record]);

            return filled($email) ? (string) $email : null;
        }

        $column = $this->getEmailColumn();

        if ($column === null) {
            return null;
        }

        $email = data_get($record, $column);

        return filled($email) ? (string) $email : null;
    }

    protected function resolveUserDisplayAvatarUrl(Model $record): ?string
    {
        if ($this->userDisplayGetAvatarUrlUsing !== null) {
            $url = $this->evaluate($this->userDisplayGetAvatarUrlUsing, ['record' => $record]);

            return filled($url) ? (string) $url : null;
        }

        if (method_exists($record, 'getFilamentAvatarUrl')) {
            $url = $record->getFilamentAvatarUrl();

            if (filled($url)) {
                return (string) $url;
            }
        }

        $column = $this->getAvatarColumn();

        if ($column === null) {
            return null;
        }

        $url = data_get($record, $column);

        return filled($url) ? (string) $url : null;
    }

    protected function resolveUserDisplayIsVerified(Model $record): bool
    {
        if ($this->userDisplayIsVerifiedUsing !== null) {
            return (bool) $this->evaluate($this->userDisplayIsVerifiedUsing, ['record' => $record]);
        }

        $column = $this->getVerificationColumn();

        if ($column === null) {
            return false;
        }

        $value = data_get($record, $column);

        if (is_bool($value)) {
            return $value;
        }

        return filled($value);
    }
}
