<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Enterprise;

use Bjanczak\FilamentFlexFields\Models\FlexFieldSchemaVersion;
use Bjanczak\FilamentFlexFields\Support\Schema\SchemaImportExport;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

final class SchemaRegistry
{
    public const string STATE_DRAFT = 'draft';

    public const string STATE_REVIEW = 'review';

    public const string STATE_LIVE = 'live';

    /** @var array<string, list<array{version: int, schema: array<string, mixed>, checksum: string, actor: ?string, state: string, published_at: string}>> */
    private static array $memoryVersions = [];

    private static ?SchemaImportExport $importExport = null;

    private static ?bool $databaseAvailable = null;

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function publish(
        string $schemaId,
        array $schema,
        ?string $actor = null,
        string $state = self::STATE_DRAFT,
        ?int $flexFieldGroupId = null,
    ): int {
        self::assertApprovalState($state);

        $checksum = self::checksum($schema);
        $publishedAt = now()->toIso8601String();

        if (self::usesDatabase()) {
            $version = (int) FlexFieldSchemaVersion::query()
                ->where('schema_id', $schemaId)
                ->max('version') + 1;

            FlexFieldSchemaVersion::query()->create([
                'flex_field_group_id' => $flexFieldGroupId,
                'schema_id' => $schemaId,
                'version' => $version,
                'schema' => $schema,
                'checksum' => $checksum,
                'actor' => $actor,
                'state' => $state,
                'published_at' => now(),
            ]);

            return $version;
        }

        $version = count(self::$memoryVersions[$schemaId] ?? []) + 1;

        self::$memoryVersions[$schemaId][] = [
            'version' => $version,
            'schema' => $schema,
            'checksum' => $checksum,
            'actor' => $actor,
            'state' => $state,
            'published_at' => $publishedAt,
        ];

        return $version;
    }

    /**
     * @return list<array{version: int, schema: array<string, mixed>, checksum: string, actor: ?string, state: string, published_at: string}>
     */
    public static function versions(string $schemaId): array
    {
        if (self::usesDatabase()) {
            return FlexFieldSchemaVersion::query()
                ->where('schema_id', $schemaId)
                ->orderBy('version')
                ->get()
                ->map(fn (FlexFieldSchemaVersion $row): array => $row->toRegistryRecord())
                ->all();
        }

        return self::$memoryVersions[$schemaId] ?? [];
    }

    /**
     * @return array{version: int, schema: array<string, mixed>, checksum: string, actor: ?string, state: string, published_at: string}
     */
    public static function rollback(string $schemaId, int $version, ?int $flexFieldGroupId = null): array
    {
        $record = self::findVersion($schemaId, $version);

        if ($record === null) {
            throw new InvalidArgumentException("Schema [{$schemaId}] has no version [{$version}].");
        }

        $newVersion = self::publish(
            $schemaId,
            $record['schema'],
            $record['actor'],
            self::STATE_LIVE,
            $flexFieldGroupId,
        );

        return self::findVersion($schemaId, $newVersion) ?? $record;
    }

    public static function setApprovalState(string $schemaId, int $version, string $state): void
    {
        self::assertApprovalState($state);

        if (self::usesDatabase()) {
            $updated = FlexFieldSchemaVersion::query()
                ->where('schema_id', $schemaId)
                ->where('version', $version)
                ->update(['state' => $state]);

            if ($updated === 0) {
                throw new InvalidArgumentException("Schema [{$schemaId}] has no version [{$version}].");
            }

            return;
        }

        $index = self::memoryVersionIndex($schemaId, $version);

        if ($index === null) {
            throw new InvalidArgumentException("Schema [{$schemaId}] has no version [{$version}].");
        }

        self::$memoryVersions[$schemaId][$index]['state'] = $state;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function checksum(array $schema): string
    {
        return self::importExport()->checksum($schema);
    }

    public static function clear(): void
    {
        self::$memoryVersions = [];
        self::$importExport = null;
        self::$databaseAvailable = null;

        if (self::persistenceEnabled() && self::tableExists()) {
            FlexFieldSchemaVersion::query()->delete();
        }
    }

    public static function usesDatabase(): bool
    {
        if (! self::persistenceEnabled()) {
            return false;
        }

        return self::tableExists();
    }

    private static function persistenceEnabled(): bool
    {
        try {
            if (! function_exists('app') || ! app()->bound('config')) {
                return false;
            }

            return (bool) config('filament-flex-fields.schema.registry_persistence', true);
        } catch (Throwable) {
            return false;
        }
    }

    private static function tableExists(): bool
    {
        if (self::$databaseAvailable !== null) {
            return self::$databaseAvailable;
        }

        try {
            if (! function_exists('app') || ! app()->bound('db')) {
                self::$databaseAvailable = false;

                return false;
            }

            self::$databaseAvailable = Schema::hasTable('flex_field_schema_versions');
        } catch (Throwable) {
            self::$databaseAvailable = false;
        }

        return self::$databaseAvailable;
    }

    private static function importExport(): SchemaImportExport
    {
        return self::$importExport ??= new SchemaImportExport;
    }

    private static function assertApprovalState(string $state): void
    {
        if (! in_array($state, [self::STATE_DRAFT, self::STATE_REVIEW, self::STATE_LIVE], true)) {
            throw new InvalidArgumentException("Invalid schema approval state [{$state}].");
        }
    }

    /**
     * @return array{version: int, schema: array<string, mixed>, checksum: string, actor: ?string, state: string, published_at: string}|null
     */
    private static function findVersion(string $schemaId, int $version): ?array
    {
        if (self::usesDatabase()) {
            $row = FlexFieldSchemaVersion::query()
                ->where('schema_id', $schemaId)
                ->where('version', $version)
                ->first();

            return $row?->toRegistryRecord();
        }

        $index = self::memoryVersionIndex($schemaId, $version);

        if ($index === null) {
            return null;
        }

        return self::$memoryVersions[$schemaId][$index];
    }

    private static function memoryVersionIndex(string $schemaId, int $version): ?int
    {
        foreach (self::$memoryVersions[$schemaId] ?? [] as $index => $record) {
            if ($record['version'] === $version) {
                return $index;
            }
        }

        return null;
    }
}
