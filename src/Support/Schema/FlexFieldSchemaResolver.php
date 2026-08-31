<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;
use Bjanczak\FilamentFlexFields\Data\FlexFieldSection;
use Bjanczak\FilamentFlexFields\Support\Enterprise\FieldRbacMatrix;
use Bjanczak\FilamentFlexFields\Support\Enterprise\TenantFieldPacks;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;

final class FlexFieldSchemaResolver
{
    public function __construct(
        private readonly FlexFieldSchemaRegistry $registry,
    ) {}

    public function resolveTenantId(?object $context = null): ?string
    {
        $resolver = FlexFieldsConfig::getSchemaTenantResolver();

        if (! is_callable($resolver)) {
            return null;
        }

        $tenantId = $resolver($context);

        if (! is_string($tenantId) || ! filled($tenantId)) {
            return null;
        }

        return $tenantId;
    }

    public function resolveRbacUserKey(?object $context = null): ?string
    {
        $resolver = FlexFieldsConfig::getSchemaRbacUserKeyResolver();

        if (! is_callable($resolver)) {
            $user = auth()->user();

            if ($user === null) {
                return null;
            }

            foreach (['email', 'id'] as $attribute) {
                if (isset($user->{$attribute}) && filled($user->{$attribute})) {
                    return (string) $user->{$attribute};
                }
            }

            return null;
        }

        $key = $resolver($context);

        return is_string($key) && filled($key) ? $key : null;
    }

    /**
     * @return list<FlexFieldSchema>
     */
    public function schemasForTarget(string $targetType, ?string $tenantId = null): array
    {
        return array_values(array_filter(
            $this->registry->forTarget($targetType),
            fn (FlexFieldSchema $schema): bool => $this->schemaMatchesTenant($schema->key, $tenantId),
        ));
    }

    /**
     * @return list<FlexFieldDefinition>
     */
    public function definitionsForTarget(
        string $targetType,
        ?string $tenantId = null,
        ?string $rbacUserKey = null,
        string $rbacAbility = FieldRbacMatrix::ABILITY_VIEW,
    ): array {
        /** @var array<string, FlexFieldDefinition> $bySlug */
        $bySlug = [];

        foreach ($this->schemasForTarget($targetType, $tenantId) as $schema) {
            foreach ($schema->getFields() as $definition) {
                if (! $this->fieldAllowedForTenant($definition, $tenantId)) {
                    continue;
                }

                if (! $this->fieldAllowedForUser($definition, $rbacUserKey, $rbacAbility)) {
                    continue;
                }

                $bySlug[$definition->slug] = $definition;
            }
        }

        return array_values(collect($bySlug)
            ->sortBy(fn (FlexFieldDefinition $definition): int => $definition->sort)
            ->values()
            ->all());
    }

    /**
     * @return list<FlexFieldSection>
     */
    public function sectionsForTarget(string $targetType, ?string $tenantId = null): array
    {
        /** @var array<string, FlexFieldSection> $byId */
        $byId = [];

        foreach ($this->schemasForTarget($targetType, $tenantId) as $schema) {
            foreach ($schema->getSections() as $section) {
                $byId[$section->id] = $section;
            }
        }

        return array_values(collect($byId)
            ->sortBy(fn (FlexFieldSection $section): int => $section->sort)
            ->values()
            ->all());
    }

    /**
     * @param  list<string>|null  $onlySlugs
     * @return list<FlexFieldDefinition>
     */
    public function definitionsForModel(
        string $modelClass,
        ?string $tenantId = null,
        ?string $rbacUserKey = null,
        ?array $onlySlugs = null,
        string $rbacAbility = FieldRbacMatrix::ABILITY_VIEW,
    ): array {
        $definitions = $this->definitionsForTarget($modelClass, $tenantId, $rbacUserKey, $rbacAbility);

        if ($onlySlugs === null) {
            return $definitions;
        }

        $allowed = array_flip($onlySlugs);

        return array_values(array_filter(
            $definitions,
            fn (FlexFieldDefinition $definition): bool => isset($allowed[$definition->slug]),
        ));
    }

    public function schemaMatchesTenant(string $schemaKey, ?string $tenantId): bool
    {
        if (! str_contains($schemaKey, ':')) {
            return true;
        }

        [$schemaTenant] = explode(':', $schemaKey, 2);

        if ($tenantId === null || $tenantId === '') {
            return false;
        }

        return $schemaTenant === $tenantId;
    }

    public function fieldAllowedForTenant(FlexFieldDefinition $definition, ?string $tenantId): bool
    {
        if ($tenantId === null || $tenantId === '') {
            return true;
        }

        $pack = TenantFieldPacks::packFor($tenantId);

        if ($pack === []) {
            return true;
        }

        return in_array($definition->type->value, $pack, true);
    }

    public function fieldAllowedForUser(
        FlexFieldDefinition $definition,
        ?string $userKey,
        string $ability = FieldRbacMatrix::ABILITY_VIEW,
    ): bool {
        if ($userKey === null || $userKey === '') {
            return true;
        }

        return FieldRbacMatrix::can($userKey, $ability, $definition->type->value);
    }
}
