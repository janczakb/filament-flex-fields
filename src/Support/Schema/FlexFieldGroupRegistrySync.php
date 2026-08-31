<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Illuminate\Support\Facades\Schema;

final class FlexFieldGroupRegistrySync
{
    public function __construct(
        protected FlexFieldSchemaRegistry $registry,
    ) {}

    public function syncGroup(FlexFieldGroup $group): void
    {
        if (! FlexFieldsConfig::shouldSyncSchemaOnSave()) {
            return;
        }

        $payload = $group->toRegistrySchema();

        $this->registry->register(
            FlexFieldSchema::make($group->registrySchemaId(), $group->getTargetType())
                ->label($group->name)
                ->sort($group->order)
                ->sections($payload['sections'] ?? [])
                ->fields($payload['fields'] ?? []),
        );
    }

    public function forgetGroup(FlexFieldGroup $group): void
    {
        if (! FlexFieldsConfig::shouldSyncSchemaOnSave()) {
            return;
        }

        $this->registry->unregister($group->registrySchemaId());
    }

    public function syncAllFromDatabase(): void
    {
        if (! FlexFieldsConfig::shouldSyncSchemaFromDatabase()) {
            return;
        }

        if (! Schema::hasTable('flex_field_groups')) {
            return;
        }

        FlexFieldGroup::query()
            ->orderBy('order')
            ->each(fn (FlexFieldGroup $group): mixed => $this->syncGroup($group));
    }
}
