<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Pages;

use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource;
use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldEntityDiscovery;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldEntityRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldSchemaResolver;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class FlexFieldManagementPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Flex field studio';

    protected static ?string $title = 'Flex field studio';

    protected static ?string $slug = 'flex-field-studio';

    protected static ?int $navigationSort = 89;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected string $view = 'filament-flex-fields::filament.pages.flex-field-management';

    public ?string $entity = null;

    /** @var list<FlexFieldGroup> */
    public array $groups = [];

    public bool $entityDiscoveryEmpty = false;

    public string $entityDiscoveryHint = '';

    public static function shouldRegisterNavigation(): bool
    {
        return FlexFieldsConfig::isSchemaManagementPageEnabled();
    }

    public static function canAccess(): bool
    {
        return FlexFieldsConfig::isSchemaManagementPageEnabled()
            && Gate::allows(FlexFieldsConfig::getSchemaPolicyAbility());
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return config(
            'filament-flex-fields.schema.navigation_group',
            FilamentFlexFieldsPlugin::make()->getNavigationGroup(),
        );
    }

    public static function getNavigationSort(): ?int
    {
        $sort = config('filament-flex-fields.schema.management_navigation_sort');

        return is_int($sort) || is_numeric($sort) ? (int) $sort : static::$navigationSort;
    }

    public function mount(?string $entity = null): void
    {
        $discovery = app(FlexFieldEntityDiscovery::class);
        $entities = app(FlexFieldEntityRegistry::class)->all();

        $this->entityDiscoveryEmpty = $entities === [];
        $this->entityDiscoveryHint = $this->entityDiscoveryEmpty
            ? $discovery->emptyStateHint()
            : '';

        if ($entities === []) {
            $this->entity = FlexFieldsConfig::getSchemaDefaultTargetType();

            $this->refreshGroups();

            return;
        }

        $available = collect($entities)->pluck('modelClass')->all();

        if ($entity !== null && in_array($entity, $available, true)) {
            $this->entity = $entity;
        } else {
            $this->entity = $entities[0]->modelClass;
        }

        $this->refreshGroups();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createGroup')
                ->label(__('filament-flex-fields::default.schema.management_create_group'))
                ->url(fn (): string => FlexFieldGroupResource::getUrl('create', [
                    'target_type' => $this->entity,
                ])),
        ];
    }

    /**
     * @return array<NavigationItem>
     */
    public function getSubNavigation(): array
    {
        $items = [];

        foreach (app(FlexFieldEntityRegistry::class)->all() as $entityModel) {
            $items[] = NavigationItem::make($entityModel->label)
                ->icon($entityModel->icon ?? 'heroicon-o-rectangle-stack')
                ->url(static::getUrl(['entity' => $entityModel->modelClass]))
                ->isActiveWhen(fn (): bool => $this->entity === $entityModel->modelClass);
        }

        if ($items === []) {
            $items[] = NavigationItem::make(__('filament-flex-fields::default.schema.management_default_entity'))
                ->icon('heroicon-o-rectangle-stack')
                ->url(static::getUrl())
                ->isActiveWhen(fn (): bool => true);
        }

        return $items;
    }

    protected function refreshGroups(): void
    {
        $this->groups = $this->groupsQuery()->get()->all();
    }

    protected function groupsQuery(): Builder
    {
        $query = FlexFieldGroup::query()->where('target_type', $this->entity ?? FlexFieldsConfig::getSchemaDefaultTargetType());

        if (! FlexFieldsConfig::shouldScopeSchemaResourceByTenant()) {
            return $query->orderBy('order');
        }

        $tenantId = app(FlexFieldSchemaResolver::class)->resolveTenantId(Filament::auth()->user());

        if ($tenantId === null) {
            return $query->orderBy('order');
        }

        return $query
            ->where(function (Builder $builder) use ($tenantId): void {
                $builder
                    ->where('tenant_id', $tenantId)
                    ->orWhere('tenant_id', '');
            })
            ->orderBy('order');
    }

    /**
     * @return array{version: int, state: string}|null
     */
    public function latestRegistryVersion(FlexFieldGroup $group): ?array
    {
        $versions = SchemaRegistry::versions($group->registrySchemaId());

        if ($versions === []) {
            return null;
        }

        /** @var array{version: int, state: string} $latest */
        $latest = collect($versions)->sortByDesc('version')->first();

        return [
            'version' => (int) $latest['version'],
            'state' => (string) $latest['state'],
        ];
    }
}
