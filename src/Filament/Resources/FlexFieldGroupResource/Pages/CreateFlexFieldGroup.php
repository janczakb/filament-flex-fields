<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages;

use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Concerns\PreparesFlexFieldGroupFormData;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Concerns\ManagesFlexFieldGroupSchemaActions;
use Filament\Resources\Pages\CreateRecord;

class CreateFlexFieldGroup extends CreateRecord
{
    use ManagesFlexFieldGroupSchemaActions;
    use PreparesFlexFieldGroupFormData;

    protected static string $resource = FlexFieldGroupResource::class;

    public function mount(): void
    {
        parent::mount();

        $targetType = request()->query('target_type');

        if (! is_string($targetType) || ! filled($targetType)) {
            return;
        }

        $this->form->fill([
            ...$this->form->getState(),
            'target_type' => $targetType,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return $this->flexFieldGroupSchemaActions();
    }
}
