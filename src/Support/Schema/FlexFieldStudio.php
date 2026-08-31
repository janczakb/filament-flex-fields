<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Data\FlexFieldSection;
use Bjanczak\FilamentFlexFields\Support\Enterprise\FieldRbacMatrix;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Column;

final class FlexFieldStudio
{
    public function __construct(
        private readonly FlexFieldSchemaResolver $resolver,
        private readonly FlexFieldFormBuilder $formBuilder,
        private readonly FlexFieldTableBuilder $tableBuilder,
        private readonly FlexFieldInfolistBuilder $infolistBuilder,
    ) {}

    public function form(): FlexFieldFormIntegration
    {
        return new FlexFieldFormIntegration($this->resolver, $this->formBuilder);
    }

    public function table(): FlexFieldTableIntegration
    {
        return new FlexFieldTableIntegration($this->resolver, $this->tableBuilder);
    }

    public function infolist(): FlexFieldInfolistIntegration
    {
        return new FlexFieldInfolistIntegration($this->resolver, $this->infolistBuilder);
    }

    public function filters(): FlexFieldFilterIntegration
    {
        return new FlexFieldFilterIntegration($this->resolver, app(FlexFieldFilterBuilder::class));
    }

    /**
     * @return list<FlexFieldDefinition>
     */
    public function definitionsForModel(string $modelClass, ?object $context = null, ?array $onlySlugs = null): array
    {
        return $this->resolver->definitionsForModel(
            $modelClass,
            $this->resolver->resolveTenantId($context),
            $this->resolver->resolveRbacUserKey($context),
            $onlySlugs,
        );
    }
}

final class FlexFieldFormIntegration
{
    private ?string $modelClass = null;

    private ?object $context = null;

    private ?string $tenantId = null;

    private ?string $rbacUserKey = null;

    private string $statePathPrefix = '';

    private ?string $sectionLabel = null;

    private bool $collapsible = false;

    /** @var list<string>|null */
    private ?array $onlySlugs = null;

    public function __construct(
        private readonly FlexFieldSchemaResolver $resolver,
        private readonly FlexFieldFormBuilder $formBuilder,
    ) {}

    public function forModel(string $modelClass): self
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    public function record(?object $record): self
    {
        $this->context = $record;

        return $this;
    }

    public function tenantId(?string $tenantId): self
    {
        $this->tenantId = $tenantId;

        return $this;
    }

    public function rbacUserKey(?string $userKey): self
    {
        $this->rbacUserKey = $userKey;

        return $this;
    }

    public function statePath(string $path): self
    {
        $this->statePathPrefix = $path;

        return $this;
    }

    public function sectionLabel(?string $label): self
    {
        $this->sectionLabel = $label;

        return $this;
    }

    public function collapsible(bool $collapsible = true): self
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    /**
     * @param  list<string>  $slugs
     */
    public function only(array $slugs): self
    {
        $this->onlySlugs = array_values($slugs);

        return $this;
    }

    /**
     * @return list<FlexFieldDefinition>
     */
    public function definitions(): array
    {
        $modelClass = $this->resolveModelClass();

        return $this->resolver->definitionsForModel(
            $modelClass,
            $this->resolveTenantId(),
            $this->resolveRbacUserKey(),
            $this->onlySlugs,
            FieldRbacMatrix::ABILITY_EDIT,
        );
    }

    /**
     * @return list<Component|Section>
     */
    public function layout(): array
    {
        $sections = $this->resolver->sectionsForTarget(
            $this->resolveModelClass(),
            $this->resolveTenantId(),
        );

        if ($sections === []) {
            return [$this->section()];
        }

        $definitions = collect($this->definitions());
        $prefix = $this->statePathPrefix !== ''
            ? $this->statePathPrefix
            : FlexFieldsConfig::getValuesColumn();

        $layout = [];

        foreach ($sections as $sectionDefinition) {
            $sectionFields = $definitions
                ->filter(fn (FlexFieldDefinition $definition): bool => $definition->sectionId === $sectionDefinition->id)
                ->values()
                ->all();

            if ($sectionFields === []) {
                continue;
            }

            $section = Section::make($sectionDefinition->label)
                ->schema($this->formBuilder->build($sectionFields, $prefix, $this->resolveRbacUserKey()));

            if ($sectionDefinition->type->value === 'fieldset') {
                $section->compact();
            }

            if ($sectionDefinition->description !== null) {
                $section->description($sectionDefinition->description);
            }

            $layout[] = $section;
        }

        $ungrouped = $definitions
            ->filter(fn (FlexFieldDefinition $definition): bool => $definition->sectionId === null)
            ->values()
            ->all();

        if ($ungrouped !== []) {
            $layout[] = Section::make($this->sectionLabel ?? __('filament-flex-fields::default.schema.custom_fields_section'))
                ->schema($this->formBuilder->build($ungrouped, $prefix, $this->resolveRbacUserKey()));
        }

        return $layout;
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $prefix = $this->statePathPrefix !== ''
            ? $this->statePathPrefix
            : FlexFieldsConfig::getValuesColumn();

        return $this->formBuilder->build(
            $this->definitions(),
            $prefix,
            $this->resolveRbacUserKey(),
        );
    }

    public function section(): Section
    {
        $section = Section::make($this->sectionLabel ?? __('filament-flex-fields::default.schema.custom_fields_section'))
            ->schema($this->components());

        if ($this->collapsible) {
            $section->collapsible();
        }

        return $section;
    }

    private function resolveModelClass(): string
    {
        if ($this->modelClass !== null) {
            return $this->modelClass;
        }

        if ($this->context !== null) {
            return $this->context::class;
        }

        return FlexFieldsConfig::getSchemaDefaultTargetType();
    }

    private function resolveTenantId(): ?string
    {
        if ($this->tenantId !== null) {
            return filled($this->tenantId) ? $this->tenantId : null;
        }

        return $this->resolver->resolveTenantId($this->context);
    }

    private function resolveRbacUserKey(): ?string
    {
        if ($this->rbacUserKey !== null) {
            return filled($this->rbacUserKey) ? $this->rbacUserKey : null;
        }

        return $this->resolver->resolveRbacUserKey($this->context);
    }
}

final class FlexFieldTableIntegration
{
    private ?string $modelClass = null;

    private ?object $context = null;

    private ?string $tenantId = null;

    private ?string $rbacUserKey = null;

    private string $valuesColumn = '';

    /** @var list<string>|null */
    private ?array $onlySlugs = null;

    public function __construct(
        private readonly FlexFieldSchemaResolver $resolver,
        private readonly FlexFieldTableBuilder $tableBuilder,
    ) {}

    public function forModel(string $modelClass): self
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    public function record(?object $record): self
    {
        $this->context = $record;

        return $this;
    }

    public function tenantId(?string $tenantId): self
    {
        $this->tenantId = $tenantId;

        return $this;
    }

    public function rbacUserKey(?string $userKey): self
    {
        $this->rbacUserKey = $userKey;

        return $this;
    }

    public function valuesColumn(string $column): self
    {
        $this->valuesColumn = $column;

        return $this;
    }

    /**
     * @param  list<string>  $slugs
     */
    public function only(array $slugs): self
    {
        $this->onlySlugs = array_values($slugs);

        return $this;
    }

    /**
     * @return list<Column>
     */
    public function columns(): array
    {
        $modelClass = $this->modelClass ?? ($this->context !== null ? $this->context::class : FlexFieldsConfig::getSchemaDefaultTargetType());

        $definitions = $this->resolver->definitionsForModel(
            $modelClass,
            $this->tenantId ?? $this->resolver->resolveTenantId($this->context),
            $this->rbacUserKey ?? $this->resolver->resolveRbacUserKey($this->context),
            $this->onlySlugs,
        );

        return $this->tableBuilder->build($definitions, $this->valuesColumn);
    }
}

final class FlexFieldInfolistIntegration
{
    private ?string $modelClass = null;

    private ?object $context = null;

    private ?string $tenantId = null;

    private ?string $rbacUserKey = null;

    private string $valuesColumn = '';

    /** @var list<string>|null */
    private ?array $onlySlugs = null;

    public function __construct(
        private readonly FlexFieldSchemaResolver $resolver,
        private readonly FlexFieldInfolistBuilder $infolistBuilder,
    ) {}

    public function forModel(string $modelClass): self
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    public function record(?object $record): self
    {
        $this->context = $record;

        return $this;
    }

    public function tenantId(?string $tenantId): self
    {
        $this->tenantId = $tenantId;

        return $this;
    }

    public function rbacUserKey(?string $userKey): self
    {
        $this->rbacUserKey = $userKey;

        return $this;
    }

    public function valuesColumn(string $column): self
    {
        $this->valuesColumn = $column;

        return $this;
    }

    /**
     * @param  list<string>  $slugs
     */
    public function only(array $slugs): self
    {
        $this->onlySlugs = array_values($slugs);

        return $this;
    }

    /**
     * @return list<Entry>
     */
    public function entries(): array
    {
        $modelClass = $this->modelClass ?? ($this->context !== null ? $this->context::class : FlexFieldsConfig::getSchemaDefaultTargetType());

        $definitions = $this->resolver->definitionsForModel(
            $modelClass,
            $this->tenantId ?? $this->resolver->resolveTenantId($this->context),
            $this->rbacUserKey ?? $this->resolver->resolveRbacUserKey($this->context),
            $this->onlySlugs,
        );

        return $this->infolistBuilder->build($definitions, $this->valuesColumn);
    }

    public function section(?string $label = null): Section
    {
        return Section::make($label ?? __('filament-flex-fields::default.schema.custom_fields_section'))
            ->schema($this->entries());
    }
}

final class FlexFieldFilterIntegration
{
    private ?string $modelClass = null;

    private ?object $context = null;

    private ?string $tenantId = null;

    private ?string $rbacUserKey = null;

    private string $valuesColumn = '';

    /** @var list<string>|null */
    private ?array $onlySlugs = null;

    public function __construct(
        private readonly FlexFieldSchemaResolver $resolver,
        private readonly FlexFieldFilterBuilder $filterBuilder,
    ) {}

    public function forModel(string $modelClass): self
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    public function record(?object $record): self
    {
        $this->context = $record;

        return $this;
    }

    /**
     * @param  list<string>  $slugs
     */
    public function only(array $slugs): self
    {
        $this->onlySlugs = array_values($slugs);

        return $this;
    }

    /**
     * @return list<\Filament\Tables\Filters\Filter>
     */
    public function filters(): array
    {
        $modelClass = $this->modelClass ?? ($this->context !== null ? $this->context::class : FlexFieldsConfig::getSchemaDefaultTargetType());

        $definitions = $this->resolver->definitionsForModel(
            $modelClass,
            $this->tenantId ?? $this->resolver->resolveTenantId($this->context),
            $this->rbacUserKey ?? $this->resolver->resolveRbacUserKey($this->context),
            $this->onlySlugs,
        );

        return $this->filterBuilder->build($definitions, $this->valuesColumn);
    }
}
