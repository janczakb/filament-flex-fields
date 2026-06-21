<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UserSelectRecordMapper
{
    public function __construct(
        protected UserSelect $select,
    ) {}

    /**
     * @return array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     * }
     */
    public function recordToOptionArray(Model $record): array
    {
        return [
            'label' => $this->resolveName($record),
            'description' => $this->resolveEmail($record),
            'image' => $this->resolveAvatarUrl($record),
            'verified' => $this->resolveIsVerified($record),
        ];
    }

    /**
     * @param  array<string, mixed>  $shape
     * @return array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     *     disabled?: bool,
     * }
     */
    public function recordToOptionArrayFromShape(string|int $value, array $shape): array
    {
        return [
            'label' => (string) ($shape['label'] ?? $value),
            'description' => filled($shape['description'] ?? null) ? (string) $shape['description'] : null,
            'image' => filled($shape['image'] ?? null) ? (string) $shape['image'] : null,
            'verified' => (bool) ($shape['verified'] ?? false),
            'disabled' => (bool) ($shape['disabled'] ?? $this->select->isOptionDisabled($value, (string) ($shape['label'] ?? $value))),
        ];
    }

    public function resolveModelKeyName(): string
    {
        $modelClass = $this->select->getUserModel();

        if ($modelClass === null) {
            return 'id';
        }

        /** @var Model $model */
        $model = new $modelClass;

        return $model->getKeyName();
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return array<int|string, array<string, mixed>>
     */
    public function mapQueryRecordsToOptions(Collection $records): array
    {
        $keyName = $this->resolveModelKeyName();

        return $records
            ->mapWithKeys(fn (Model $record): array => [
                $record->getAttribute($keyName) => $this->recordToOptionArray($record),
            ])
            ->all();
    }

    public function resolveName(Model $record): string
    {
        $callback = $this->select->getNameResolver();

        if ($callback !== null) {
            return (string) $this->select->evaluate($callback, ['record' => $record]);
        }

        return (string) data_get($record, $this->select->getNameColumn());
    }

    public function resolveEmail(Model $record): ?string
    {
        $callback = $this->select->getEmailResolver();

        if ($callback !== null) {
            $email = $this->select->evaluate($callback, ['record' => $record]);

            return filled($email) ? (string) $email : null;
        }

        $column = $this->select->getEmailColumn();

        if ($column === null) {
            return null;
        }

        $email = data_get($record, $column);

        return filled($email) ? (string) $email : null;
    }

    public function resolveAvatarUrl(Model $record): ?string
    {
        $callback = $this->select->getAvatarUrlResolver();

        if ($callback !== null) {
            $url = $this->select->evaluate($callback, ['record' => $record]);

            return filled($url) ? (string) $url : null;
        }

        $column = $this->select->getAvatarColumn();

        if ($column === null) {
            return null;
        }

        $url = data_get($record, $column);

        return filled($url) ? (string) $url : null;
    }

    public function resolveIsVerified(Model $record): bool
    {
        $callback = $this->select->getVerifiedResolver();

        if ($callback !== null) {
            return (bool) $this->select->evaluate($callback, ['record' => $record]);
        }

        $column = $this->select->getVerificationColumn();

        if ($column === null) {
            return false;
        }

        $value = data_get($record, $column);

        if (is_bool($value)) {
            return $value;
        }

        return filled($value);
    }

    public function needsFullModelForResolvers(): bool
    {
        return $this->select->getNameResolver() !== null
            || $this->select->getEmailResolver() !== null
            || $this->select->getAvatarUrlResolver() !== null
            || $this->select->getVerifiedResolver() !== null;
    }
}
