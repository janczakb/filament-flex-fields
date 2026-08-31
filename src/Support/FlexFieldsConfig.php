<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Enums\Density;

class FlexFieldsConfig
{
    public static function isEnabled(): bool
    {
        return (bool) config('filament-flex-fields.enabled', true);
    }

    public static function getValuesColumn(): string
    {
        return (string) config('filament-flex-fields.values_column', 'flex_field_values');
    }

    public static function isAuditEnabled(): bool
    {
        return (bool) config('filament-flex-fields.audit.enabled', true);
    }

    public static function getAuditColumn(): string
    {
        return (string) config('filament-flex-fields.audit.column', 'flex_field_audit');
    }

    public static function getAuditMaxEntries(): int
    {
        return max(1, (int) config('filament-flex-fields.audit.max_entries', 500));
    }

    public static function allowHttpMedia(): bool
    {
        return (bool) config('filament-flex-fields.security.allow_http_media', false);
    }

    public static function isPlaygroundEnabled(): bool
    {
        return (bool) config('filament-flex-fields.playground.enabled', false);
    }

    public static function isSchemaResourceEnabled(): bool
    {
        return (bool) config('filament-flex-fields.schema.resource_enabled', false);
    }

    public static function isSchemaManagementPageEnabled(): bool
    {
        return (bool) config('filament-flex-fields.schema.management_page_enabled', true)
            && self::isSchemaResourceEnabled();
    }

    public static function shouldDiscoverEntitiesFromFilamentResources(): bool
    {
        return (bool) config('filament-flex-fields.schema.entity_discovery.from_filament_resources', true);
    }

    /**
     * @return list<string>
     */
    public static function getEntityDiscoveryPaths(): array
    {
        $paths = config('filament-flex-fields.schema.entity_discovery.paths', []);

        return is_array($paths) ? array_values(array_filter($paths, is_string(...))) : [];
    }

    public static function getEntityDiscoveryNamespace(): ?string
    {
        $namespace = config('filament-flex-fields.schema.entity_discovery.namespace');

        return is_string($namespace) && filled($namespace) ? $namespace : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getConfiguredEntities(): array
    {
        $entities = config('filament-flex-fields.schema.entities', []);

        return is_array($entities) ? $entities : [];
    }

    public static function shouldSyncSchemaFromDatabase(): bool
    {
        return (bool) config('filament-flex-fields.schema.sync_from_database', true);
    }

    public static function shouldSyncSchemaOnSave(): bool
    {
        return (bool) config('filament-flex-fields.schema.sync_on_save', true);
    }

    public static function getSchemaDefaultTargetType(): string
    {
        return (string) config('filament-flex-fields.schema.default_target_type', 'App\\Models\\Model');
    }

    public static function getSchemaPolicyAbility(): string
    {
        return (string) config('filament-flex-fields.schema.policy_ability', 'manageFlexFieldSchemas');
    }

    /**
     * @return callable|null
     */
    public static function getSchemaTenantResolver(): mixed
    {
        return config('filament-flex-fields.schema.tenant_resolver');
    }

    /**
     * @return callable|null
     */
    public static function getSchemaRbacUserKeyResolver(): mixed
    {
        return config('filament-flex-fields.schema.rbac_user_key_resolver');
    }

    public static function shouldScopeSchemaResourceByTenant(): bool
    {
        return (bool) config('filament-flex-fields.schema.scope_resource_by_tenant', false);
    }

    public static function getPlaygroundNavigationGroup(): ?string
    {
        $group = config('filament-flex-fields.playground.navigation_group');

        return is_string($group) ? $group : null;
    }

    public static function getPlaygroundNavigationSort(): ?int
    {
        $sort = config('filament-flex-fields.playground.navigation_sort');

        return is_int($sort) || is_numeric($sort) ? (int) $sort : null;
    }

    public static function getMapboxAccessToken(): ?string
    {
        $token = config('filament-flex-fields.mapbox.access_token');

        return is_string($token) && filled($token) ? $token : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getCurrencies(): array
    {
        return (array) config('filament-flex-fields.currencies', []);
    }

    public static function getSlugFieldTitle(): string
    {
        return (string) config('filament-flex-fields.slug.field_title', 'title');
    }

    public static function getSlugFieldSlug(): string
    {
        return (string) config('filament-flex-fields.slug.field_slug', 'slug');
    }

    public static function getSlugUrlHost(): ?string
    {
        $host = config('filament-flex-fields.slug.url_host');

        return is_string($host) && filled($host) ? $host : null;
    }

    /**
     * @return array<string, string>|null
     */
    public static function getSlugTranslatableLocales(): ?array
    {
        $locales = config('filament-flex-fields.slug.translatable_locales');

        return is_array($locales) ? $locales : null;
    }

    public static function getSlugSourceLocale(): ?string
    {
        $locale = config('filament-flex-fields.slug.slug_source_locale');

        return is_string($locale) && filled($locale) ? $locale : null;
    }

    public static function getSlugRequiredTitleLocales(): mixed
    {
        return config('filament-flex-fields.slug.required_title_locales');
    }

    public static function isSlugSpatieTranslatable(): bool
    {
        return (bool) config('filament-flex-fields.slug.spatie_translatable', false);
    }

    public static function getUiDefault(string $key, mixed $default = null): mixed
    {
        return config("filament-flex-fields.ui.{$key}", $default);
    }

    public static function getDensity(): Density
    {
        $density = config('filament-flex-fields.ui.density', Density::default()->value);

        return Density::fromMixed(is_string($density) ? $density : Density::default()->value);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getTheme(): array
    {
        $theme = config('filament-flex-fields.ui.theme', []);

        return is_array($theme) ? $theme : [];
    }

    /**
     * @return array<string, string>|list<string>|null
     */
    public static function getTranslatableLocales(): ?array
    {
        $locales = config('filament-flex-fields.translatable.locales')
            ?? config('filament-flex-fields.slug.translatable_locales');

        return is_array($locales) ? $locales : null;
    }

    /**
     * @return array<string, string>|null
     */
    public static function getTranslatableLocaleLabels(): ?array
    {
        $labels = config('filament-flex-fields.translatable.locale_labels');

        return is_array($labels) ? $labels : null;
    }
}
