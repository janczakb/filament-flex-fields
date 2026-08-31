<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages;

use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldSchemaResolver;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListFlexFieldGroups extends ListRecords
{
    protected static string $resource = FlexFieldGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if (! FlexFieldsConfig::shouldScopeSchemaResourceByTenant()) {
            return $query;
        }

        $tenantId = app(FlexFieldSchemaResolver::class)->resolveTenantId(auth()->user());

        if ($tenantId === null) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($tenantId): void {
            $builder
                ->where('tenant_id', $tenantId)
                ->orWhere('tenant_id', '');
        });
    }
}
