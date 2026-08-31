<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Persisted SchemaRegistry version row (M8).
 *
 * @property int $id
 * @property int|null $flex_field_group_id
 * @property string $schema_id
 * @property int $version
 * @property array<string, mixed> $schema
 * @property string $checksum
 * @property string|null $actor
 * @property string $state
 * @property Carbon $published_at
 */
class FlexFieldSchemaVersion extends Model
{
    protected $table = 'flex_field_schema_versions';

    protected $fillable = [
        'flex_field_group_id',
        'schema_id',
        'version',
        'schema',
        'checksum',
        'actor',
        'state',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flex_field_group_id' => 'integer',
            'schema' => 'array',
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FlexFieldGroup, $this>
     */
    public function flexFieldGroup(): BelongsTo
    {
        return $this->belongsTo(FlexFieldGroup::class);
    }

    /**
     * @return array{version: int, schema: array<string, mixed>, checksum: string, actor: ?string, state: string, published_at: string}
     */
    public function toRegistryRecord(): array
    {
        return [
            'version' => $this->version,
            'schema' => $this->schema ?? [],
            'checksum' => $this->checksum,
            'actor' => $this->actor,
            'state' => $this->state,
            'published_at' => $this->published_at->toIso8601String(),
        ];
    }
}
