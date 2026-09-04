<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages;

use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Concerns\ManagesFlexFieldGroupSchemaActions;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Concerns\ManagesFlexFieldValueCsvActions;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Concerns\PreparesFlexFieldGroupFormData;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupRegistrySync;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditFlexFieldGroup extends EditRecord
{
    use ManagesFlexFieldGroupSchemaActions;
    use ManagesFlexFieldValueCsvActions;
    use PreparesFlexFieldGroupFormData;

    protected static string $resource = FlexFieldGroupResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            ...$this->flexFieldGroupSchemaActions($record),
            ...$this->flexFieldValueCsvActions($record),
            Action::make('rollbackRegistryVersion')
                ->label(__('filament-flex-fields::default.schema.rollback_version'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->authorize('rollback')
                ->schema([
                    Select::make('version')
                        ->label(__('filament-flex-fields::default.schema.rollback_version_select'))
                        ->options(function (): array {
                            return collect(SchemaRegistry::versions($this->getRecord()->registrySchemaId()))
                                ->mapWithKeys(function (array $version): array {
                                    $label = sprintf(
                                        'v%d · %s · %s',
                                        $version['version'],
                                        $version['state'],
                                        $version['published_at'],
                                    );

                                    return [$version['version'] => $label];
                                })
                                ->all();
                        })
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();
                    $rolled = $record->rollbackRegistryVersion((int) $data['version']);

                    app(FlexFieldGroupRegistrySync::class)->syncGroup($record->fresh());
                    $this->fillForm($record->fresh()->toArray());

                    Notification::make()
                        ->title(__('filament-flex-fields::default.schema.rollback_version_success', [
                            'version' => $rolled['version'],
                        ]))
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => SchemaRegistry::versions($this->getRecord()->registrySchemaId()) !== []),
            DeleteAction::make(),
        ];
    }
}
