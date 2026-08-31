<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Concerns;

use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupRegistrySync;
use Bjanczak\FilamentFlexFields\Support\Schema\SchemaBlueprintPacks;
use Bjanczak\FilamentFlexFields\Support\Schema\SchemaImportExport;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use JsonException;

trait ManagesFlexFieldGroupSchemaActions
{
    use PreparesFlexFieldGroupFormData;

    /**
     * @return list<Action|ActionGroup>
     */
    protected function flexFieldGroupSchemaActions(?FlexFieldGroup $record = null): array
    {
        return [
            ActionGroup::make([
                $this->makePublishSchemaAction(SchemaRegistry::STATE_DRAFT, 'draft')->authorize('publish'),
                $this->makePublishSchemaAction(SchemaRegistry::STATE_REVIEW, 'review')->authorize('publish'),
                $this->makePublishSchemaAction(SchemaRegistry::STATE_LIVE, 'live')->authorize('publish'),
            ])
                ->label(__('filament-flex-fields::default.schema.publish_group'))
                ->icon('heroicon-o-arrow-up-tray')
                ->button()
                ->visible($record !== null),
            Action::make('importSchemaJson')
                ->label(__('filament-flex-fields::default.schema.import_json'))
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Textarea::make('json')
                        ->label(__('filament-flex-fields::default.schema.import_json_payload'))
                        ->required()
                        ->rows(12),
                ])
                ->action(function (array $data) use ($record): void {
                    $this->applyImportedSchema($data['json'], $record);
                }),
            Action::make('exportSchemaJson')
                ->label(__('filament-flex-fields::default.schema.export_json'))
                ->icon('heroicon-o-arrow-up-on-square')
                ->visible($record !== null)
                ->action(function () use ($record): void {
                    $payload = app(SchemaImportExport::class)->export($record->toRegistrySchema());

                    Notification::make()
                        ->title(__('filament-flex-fields::default.schema.export_json_success'))
                        ->body(Str::limit($payload, 1200))
                        ->success()
                        ->persistent()
                        ->send();
                }),
            Action::make('applyBlueprintPack')
                ->label(__('filament-flex-fields::default.schema.apply_blueprint'))
                ->icon('heroicon-o-sparkles')
                ->form([
                    Select::make('pack')
                        ->label(__('filament-flex-fields::default.schema.blueprint_select'))
                        ->options(collect(SchemaBlueprintPacks::names())
                            ->mapWithKeys(fn (string $name): array => [$name => Str::headline($name)])
                            ->all())
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) use ($record): void {
                    $pack = SchemaBlueprintPacks::pack((string) $data['pack']);

                    if ($pack === null) {
                        Notification::make()
                            ->title(__('filament-flex-fields::default.schema.blueprint_not_found'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->applyBlueprintPack($pack, $record);
                }),
        ];
    }

    protected function makePublishSchemaAction(string $state, string $actionKey): Action
    {
        return Action::make("publishSchema{$actionKey}")
            ->label(__('filament-flex-fields::default.schema.publish_'.$actionKey))
            ->requiresConfirmation()
            ->modalHeading(__('filament-flex-fields::default.schema.publish_'.$actionKey))
            ->modalDescription(__('filament-flex-fields::default.schema.publish_'.$actionKey.'_help'))
            ->action(function () use ($state): void {
                /** @var FlexFieldGroup $record */
                $record = $this->getRecord();

                $user = auth()->user();
                $actor = is_object($user) && isset($user->email) ? (string) $user->email : (string) auth()->id();

                $version = $record->publishToRegistry($actor, $state);

                if ($state === SchemaRegistry::STATE_LIVE) {
                    app(FlexFieldGroupRegistrySync::class)->syncGroup($record->fresh());
                }

                Notification::make()
                    ->title(__('filament-flex-fields::default.schema.publish_version_success', ['version' => $version]))
                    ->success()
                    ->send();
            });
    }

    /**
     * @param  array<string, mixed>  $pack
     */
    protected function applyBlueprintPack(array $pack, ?FlexFieldGroup $record = null): void
    {
        $fields = $pack['fields'] ?? [];

        $formData = [
            'name' => $pack['label'] ?? ($record?->name ?? 'Blueprint group'),
            'slug' => $record?->slug ?? Str::slug((string) ($pack['key'] ?? 'blueprint')),
            'target_type' => $pack['target'] ?? ($record?->target_type ?? config('filament-flex-fields.schema.default_target_type')),
            'fields' => is_array($fields) ? array_values($fields) : [],
        ];

        if ($record !== null) {
            $record->fill($formData)->save();
            $this->fillForm($this->prepareFlexFieldGroupFormData($record->fresh()->toArray()));

            Notification::make()
                ->title(__('filament-flex-fields::default.schema.blueprint_applied'))
                ->success()
                ->send();

            return;
        }

        $this->form->fill($this->prepareFlexFieldGroupFormData(array_merge($this->form->getState(), $formData)));

        Notification::make()
            ->title(__('filament-flex-fields::default.schema.blueprint_applied'))
            ->success()
            ->send();
    }

    protected function applyImportedSchema(string $json, ?FlexFieldGroup $record = null): void
    {
        try {
            $schema = app(SchemaImportExport::class)->import($json);
        } catch (JsonException $exception) {
            Notification::make()
                ->title(__('filament-flex-fields::default.schema.import_json_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $validation = app(SchemaImportExport::class)->dryRunValidate($schema);

        if (! $validation['ok']) {
            Notification::make()
                ->title(__('filament-flex-fields::default.schema.import_json_failed'))
                ->body(implode("\n", $validation['errors']))
                ->danger()
                ->send();

            return;
        }

        $schemaKey = (string) ($schema['key'] ?? ($record?->slug ?? 'imported-group'));
        $slug = $schemaKey;

        if (str_contains($schemaKey, ':')) {
            [, $slug] = explode(':', $schemaKey, 2);
        }

        $formData = [
            'name' => $schema['label'] ?? ($record?->name ?? 'Imported group'),
            'slug' => $slug,
            'target_type' => $schema['target'] ?? ($record?->target_type ?? config('filament-flex-fields.schema.default_target_type')),
            'fields' => array_values($schema['fields'] ?? []),
        ];

        if ($record !== null) {
            $record->fill($formData)->save();
            $this->fillForm($this->prepareFlexFieldGroupFormData($record->fresh()->toArray()));
        } else {
            $this->form->fill($this->prepareFlexFieldGroupFormData(array_merge($this->form->getState(), $formData)));
        }

        Notification::make()
            ->title(__('filament-flex-fields::default.schema.import_json_success'))
            ->success()
            ->send();
    }
}
