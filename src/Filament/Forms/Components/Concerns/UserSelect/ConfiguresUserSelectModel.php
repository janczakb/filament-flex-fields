<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\UserSelect;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin UserSelect
 */
trait ConfiguresUserSelectModel
{
    protected string|Closure|null $userModel = null;

    protected ?Closure $modifyQueryUsing = null;

    protected bool $modelBindingsConfigured = false;

    public function optionModel(string|Closure $model): static
    {
        $this->userModel = $model;

        $this->configureModelBindingsIfNeeded();

        return $this;
    }

    public function query(Closure $query): static
    {
        $this->modifyQueryUsing = $query;

        return $this;
    }

    public function relationship(string|Closure|null $name = null, string|Closure|null $titleAttribute = null, ?Closure $modifyQueryUsing = null, bool $ignoreRecord = false): static
    {
        parent::relationship($name, $titleAttribute, $modifyQueryUsing, $ignoreRecord);

        if ($titleAttribute !== null) {
            $this->nameColumn($titleAttribute);
        }

        $this->getOptionLabelFromRecordUsing(function (UserSelect $component, Model $record): array {
            return $component->recordToOptionArray($record);
        });

        $this->getOptionLabelUsing(function (UserSelect $component): ?string {
            $record = $component->getSelectedRecord();

            if (! $record instanceof Model) {
                return null;
            }

            return $component->renderUserOption(
                $component->recordToOptionArray($record),
                layout: 'list',
            );
        });

        $this->getOptionLabelsUsing(function (UserSelect $component, array $values): array {
            return $component->resolveOptionLabelsForValues($values);
        });

        return $this;
    }

    public function hasClientSideOptionList(): bool
    {
        if ($this->getUserModel() !== null) {
            return false;
        }

        return parent::hasClientSideOptionList();
    }

    public function hasDynamicOptions(): bool
    {
        if ($this->getUserModel() !== null) {
            return true;
        }

        return parent::hasDynamicOptions();
    }

    public function hasInitialNoOptionsMessage(): bool
    {
        if ($this->getUserModel() !== null) {
            return false;
        }

        return parent::hasInitialNoOptionsMessage();
    }

    public function getUserModel(): ?string
    {
        $model = $this->evaluate($this->userModel);

        return is_string($model) && filled($model) ? $model : null;
    }

    public function getModifyQueryUsing(): ?Closure
    {
        return $this->modifyQueryUsing;
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function getDefaultSuggestions(): array
    {
        if ($this->getUserModel() === null) {
            return [];
        }

        return $this->queryEngine()->searchRecords(null);
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function searchRecords(?string $search): array
    {
        return $this->queryEngine()->searchRecords($search);
    }

    protected function configureModelBindingsIfNeeded(): void
    {
        if ($this->modelBindingsConfigured || $this->getUserModel() === null) {
            return;
        }

        $this->modelBindingsConfigured = true;

        $this->searchable();
        $this->searchDebounce(350);

        $this->getSearchResultsUsing(function (UserSelect $component, ?string $search): array {
            return $component->searchRecords($search);
        });

        $this->getOptionLabelUsing(function (UserSelect $component): ?string {
            $state = $component->getState();

            if ($state instanceof BackedEnum) {
                $state = $state->value;
            }

            if (blank($state)) {
                return null;
            }

            $record = $component->queryEngine()->resolveRecordForValue($state);

            if (! $record instanceof Model) {
                return null;
            }

            return $component->renderUserOption(
                $component->recordToOptionArray($record),
                layout: 'trigger',
            );
        });
    }
}
