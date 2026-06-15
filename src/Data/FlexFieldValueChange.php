<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Data;

readonly class FlexFieldValueChange
{
    public function __construct(
        public string $slug,
        public mixed $oldValue,
        public mixed $newValue,
        public ?int $userId = null,
        public ?string $userName = null,
        public ?string $changedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'old_value' => $this->oldValue,
            'new_value' => $this->newValue,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'changed_at' => $this->changedAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            slug: (string) $attributes['slug'],
            oldValue: $attributes['old_value'] ?? $attributes['oldValue'] ?? null,
            newValue: $attributes['new_value'] ?? $attributes['newValue'] ?? null,
            userId: isset($attributes['user_id']) ? (int) $attributes['user_id'] : (isset($attributes['userId']) ? (int) $attributes['userId'] : null),
            userName: $attributes['user_name'] ?? $attributes['userName'] ?? null,
            changedAt: $attributes['changed_at'] ?? $attributes['changedAt'] ?? null,
        );
    }
}
