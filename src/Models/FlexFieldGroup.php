<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Models;

use Bjanczak\FilamentFlexFields\Database\Factories\FlexFieldGroupFactory;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupRegistrySync;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupValidator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Admin-managed JSON flex-field group (M8 schema product).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $target_type
 * @property array<int, array<string, mixed>>|null $fields
 * @property int $order
 * @property string $tenant_id
 */
class FlexFieldGroup extends Model
{
    /** @use HasFactory<FlexFieldGroupFactory> */
    use HasFactory;

    protected $table = 'flex_field_groups';

    protected $fillable = [
        'name',
        'slug',
        'target_type',
        'fields',
        'sections',
        'order',
        'tenant_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'sections' => 'array',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FlexFieldGroup $group): void {
            if ($group->tenant_id === null) {
                $group->tenant_id = '';
            }

            $validator = app(FlexFieldGroupValidator::class);
            $group->fields = $validator->normalizeFields($group->fields);
            $validator->assertValidGroup($group);
        });

        static::updating(function (FlexFieldGroup $group): void {
            if (! $group->isDirty(['slug', 'tenant_id'])) {
                return;
            }

            $previousSchemaId = self::registrySchemaIdFrom(
                (string) $group->getOriginal('slug'),
                $group->getOriginal('tenant_id'),
            );

            $nextSchemaId = self::registrySchemaIdFrom(
                (string) $group->slug,
                $group->tenant_id,
            );

            if ($previousSchemaId === $nextSchemaId) {
                return;
            }

            $previous = new self;
            $previous->slug = (string) $group->getOriginal('slug');
            $previous->tenant_id = (string) ($group->getOriginal('tenant_id') ?? '');

            app(FlexFieldGroupRegistrySync::class)->forgetGroup($previous);
        });

        static::saved(function (FlexFieldGroup $group): void {
            app(FlexFieldGroupRegistrySync::class)->syncGroup($group);
        });

        static::deleted(function (FlexFieldGroup $group): void {
            app(FlexFieldGroupRegistrySync::class)->forgetGroup($group);
        });
    }

    public static function registrySchemaIdFrom(?string $slug, ?string $tenantId): string
    {
        if (filled($tenantId)) {
            return (string) $tenantId.':'.(string) $slug;
        }

        return (string) $slug;
    }

    protected static function newFactory(): FlexFieldGroupFactory
    {
        return FlexFieldGroupFactory::new();
    }

    /**
     * @return HasMany<FlexFieldSchemaVersion, $this>
     */
    public function schemaVersions(): HasMany
    {
        return $this->hasMany(FlexFieldSchemaVersion::class);
    }

    public function getTargetType(): string
    {
        if (filled($this->target_type)) {
            return (string) $this->target_type;
        }

        return FlexFieldsConfig::getSchemaDefaultTargetType();
    }

    /**
     * Schema id used by {@see SchemaRegistry} (slug, optionally scoped by tenant).
     */
    public function registrySchemaId(): string
    {
        return self::registrySchemaIdFrom($this->slug, $this->tenant_id);
    }

    /**
     * Payload shape compatible with {@see SchemaImportExport} / blueprint packs.
     *
     * @return array<string, mixed>
     */
    public function toRegistrySchema(): array
    {
        return [
            'key' => $this->registrySchemaId(),
            'label' => $this->name,
            'target' => $this->getTargetType(),
            'version' => 1,
            'sections' => array_values($this->sections ?? []),
            'fields' => array_values($this->fields ?? []),
        ];
    }

    public function publishToRegistry(?string $actor = null, string $state = SchemaRegistry::STATE_DRAFT): int
    {
        return SchemaRegistry::publish(
            $this->registrySchemaId(),
            $this->toRegistrySchema(),
            $actor,
            $state,
            $this->id,
        );
    }

    /**
     * @return array{version: int, schema: array<string, mixed>, checksum: string, actor: ?string, state: string, published_at: string}
     */
    public function rollbackRegistryVersion(int $version): array
    {
        $record = SchemaRegistry::rollback($this->registrySchemaId(), $version, $this->id);

        $schema = $record['schema'];
        $fields = $schema['fields'] ?? [];

        if (is_array($fields)) {
            $this->fields = array_values($fields);

            if (isset($schema['label']) && is_string($schema['label']) && $schema['label'] !== '') {
                $this->name = $schema['label'];
            }

            if (isset($schema['target']) && is_string($schema['target']) && $schema['target'] !== '') {
                $this->target_type = $schema['target'];
            }

            $this->save();
        }

        return $record;
    }
}
