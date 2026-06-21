<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\UserSelect;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @mixin UserSelect
 */
trait ConfiguresUserSelectColumns
{
    protected string|Closure $nameColumn = 'name';

    protected string|Closure|null $emailColumn = null;

    protected string|Closure|null $avatarColumn = null;

    protected string|Closure|null $verificationColumn = null;

    protected ?Closure $getAvatarUrlUsing = null;

    protected ?Closure $getNameUsing = null;

    protected ?Closure $getEmailUsing = null;

    protected ?Closure $isVerifiedUsing = null;

    public function avatarColumn(string|Closure|null $column): static
    {
        $this->avatarColumn = $column;

        return $this;
    }

    public function nameColumn(string|Closure $column): static
    {
        $this->nameColumn = $column;

        return $this;
    }

    public function emailColumn(string|Closure|null $column): static
    {
        $this->emailColumn = $column;

        return $this;
    }

    public function verificationColumn(string|Closure|null $column): static
    {
        $this->verificationColumn = $column;

        return $this;
    }

    public function getAvatarUrlUsing(?Closure $callback): static
    {
        $this->getAvatarUrlUsing = $callback;

        return $this;
    }

    public function getNameUsing(?Closure $callback): static
    {
        $this->getNameUsing = $callback;

        return $this;
    }

    public function getEmailUsing(?Closure $callback): static
    {
        $this->getEmailUsing = $callback;

        return $this;
    }

    public function isVerifiedUsing(?Closure $callback): static
    {
        $this->isVerifiedUsing = $callback;

        return $this;
    }

    public function getNameColumn(): string
    {
        return (string) $this->evaluate($this->nameColumn);
    }

    public function getEmailColumn(): ?string
    {
        $column = $this->evaluate($this->emailColumn);

        return filled($column) ? (string) $column : null;
    }

    public function getAvatarColumn(): ?string
    {
        $column = $this->evaluate($this->avatarColumn);

        return filled($column) ? (string) $column : null;
    }

    public function getVerificationColumn(): ?string
    {
        $column = $this->evaluate($this->verificationColumn);

        return filled($column) ? (string) $column : null;
    }

    public function getNameResolver(): ?Closure
    {
        return $this->getNameUsing;
    }

    public function getEmailResolver(): ?Closure
    {
        return $this->getEmailUsing;
    }

    public function getAvatarUrlResolver(): ?Closure
    {
        return $this->getAvatarUrlUsing;
    }

    public function getVerifiedResolver(): ?Closure
    {
        return $this->isVerifiedUsing;
    }

    public function getSelectedRecordResolver(): mixed
    {
        return $this->getSelectedRecordUsing;
    }

    public function getQualifiedRelatedKeyForRelationship(Relation $relationship): string
    {
        return $this->getQualifiedRelatedKeyNameForRelationship($relationship);
    }

    public function findOptionLabelForState(array $options, mixed $state): array|string|null
    {
        return $this->findOptionLabel($options, $state);
    }
}
