<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Spatie Tags integration for {@see TagsField}.
 *
 * Requires `spatie/laravel-tags` and a model using `Spatie\Tags\HasTags`.
 *
 * @see https://github.com/spatie/laravel-tags
 */
class FlexSpatieTagsField extends TagsField
{
    protected string|Closure|null $spatieTagType = null;

    protected bool $allowAnySpatieTagType = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSpatieRelationship();
        $this->configureSpatieTagSearch();
    }

    protected function configureSpatieTagSearch(): void
    {
        $this->getSearchResultsUsing(function (FlexSpatieTagsField $component, string $search): array {
            if (! class_exists($component->getTagClassName())) {
                return [];
            }

            $tagClass = $component->getTagClassName();
            $type = $component->getSpatieTagType();
            $escaped = addcslashes($search, '%_\\');

            $query = $tagClass::query()
                ->where('name', 'like', '%'.$escaped.'%');

            if (! $component->allowsAnySpatieTagType()) {
                $query->when(
                    filled($type),
                    fn (Builder $query) => $query->where('type', $type),
                    fn (Builder $query) => $query->whereNull('type'),
                );
            }

            return $query
                ->orderBy('name')
                ->limit(20)
                ->pluck('name')
                ->all();
        });
    }

    protected function configureSpatieRelationship(): void
    {
        $this->loadStateFromRelationshipsUsing(static function (FlexSpatieTagsField $component, ?Model $record): void {
            if (! $record || ! method_exists($record, 'tagsWithType')) {
                return;
            }

            $record->load('tags');

            if ($component->allowsAnySpatieTagType()) {
                $tags = $record->getRelationValue('tags');
            } else {
                $tags = $record->tagsWithType($component->getSpatieTagType());
            }

            $component->state($tags->pluck('name')->all());
        });

        $this->saveRelationshipsUsing(static function (FlexSpatieTagsField $component, ?Model $record, array $state): void {
            if (! $record || ! method_exists($record, 'syncTagsWithType') || ! method_exists($record, 'syncTags')) {
                return;
            }

            if (! $component->allowsAnySpatieTagType()) {
                $record->syncTagsWithType($state, $component->getSpatieTagType());
                $record->unsetRelation('tags');

                return;
            }

            $component->syncSpatieTagsWithAnyType($record, $state);
            $record->unsetRelation('tags');
        });

        $this->dehydrated(false);
    }

    /**
     * Group tags into a Spatie collection type (same API as Filament `SpatieTagsInput`).
     *
     * Pass `null` to allow tags of any type.
     */
    public function type(string|Closure|null $type): static
    {
        if ($type === null) {
            $this->allowAnySpatieTagType = true;
            $this->spatieTagType = null;
        } else {
            $this->allowAnySpatieTagType = false;
            $this->spatieTagType = $type;
        }

        return $this;
    }

    public function getSpatieTagType(): ?string
    {
        $type = $this->evaluate($this->spatieTagType);

        return is_string($type) ? $type : null;
    }

    public function allowsAnySpatieTagType(): bool
    {
        return $this->allowAnySpatieTagType;
    }

    /**
     * @return class-string
     */
    public function getTagClassName(): string
    {
        $model = $this->getModel();

        if ($model && method_exists($model, 'getTagClassName')) {
            return $model::getTagClassName();
        }

        $configured = config('tags.tag_model', 'Spatie\\Tags\\Tag');

        if (! is_string($configured) || ! class_exists($configured)) {
            throw new RuntimeException(
                'spatie/laravel-tags is required for FlexSpatieTagsField. Install it with: composer require spatie/laravel-tags',
            );
        }

        return $configured;
    }

    /**
     * @return array<string>
     */
    public function getSuggestions(): array
    {
        if ($this->suggestions !== null) {
            return parent::getSuggestions();
        }

        return [];
    }

    /**
     * @param  array<string>  $state
     */
    protected function syncSpatieTagsWithAnyType(?Model $record, array $state): void
    {
        if (! $record || ! method_exists($record, 'tags')) {
            return;
        }

        $tagClassName = $this->getTagClassName();

        $tags = collect($state)->map(function (string $tagName) use ($tagClassName) {
            $locale = $tagClassName::getLocale();

            $tag = $tagClassName::findFromStringOfAnyType($tagName, $locale);

            if ($tag?->isEmpty() ?? true) {
                $tag = $tagClassName::create([
                    'name' => [$locale => $tagName],
                ]);
            }

            return $tag;
        })->flatten();

        $record->tags()->sync($tags->pluck('id'));
    }
}
