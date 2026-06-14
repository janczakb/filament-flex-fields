<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\ConfiguresUserSelectSearch;
use Bjanczak\FilamentFlexFields\Support\UserSelectQueryCache;
use Closure;
use Filament\Support\Enums\IconSize;
use Filament\Support\Services\RelationshipJoiner;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class UserSelect extends SelectField
{
    use ConfiguresUserSelectSearch;

    protected string $view = 'filament-flex-fields::forms.components.user-select';

    protected string|Closure|null $userModel = null;

    protected ?Closure $modifyQueryUsing = null;

    protected string|Closure $nameColumn = 'name';

    protected string|Closure|null $emailColumn = null;

    protected string|Closure|null $avatarColumn = null;

    protected string|Closure|null $verificationColumn = null;

    protected ?Closure $getAvatarUrlUsing = null;

    protected ?Closure $getNameUsing = null;

    protected ?Closure $getEmailUsing = null;

    protected ?Closure $isVerifiedUsing = null;

    /**
     * @var array<string, array<int|string, array<string, mixed>>>
     */
    protected array $searchResultsCache = [];

    protected bool $modelBindingsConfigured = false;

    /**
     * @var array<string, array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     * }>
     */
    protected array $resolvedOptionShapeCache = [];

    /**
     * @var array<string, Model>
     */
    protected array $resolvedRecordCache = [];

    /**
     * @var ?list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>
     */
    protected ?array $cachedSelectedUsersForDisplay = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->richOptions();
        $this->allowHtml();

        $this->getOptionLabelsUsing(function (UserSelect $component): array {
            if (! $component->isMultiple()) {
                return [];
            }

            $state = $component->getState();

            if (! is_array($state)) {
                return [];
            }

            return $component->resolveOptionLabelsForValues($state);
        });

        $this->configureModelBindingsIfNeeded();
    }

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

    public function avatarColumn(string|Closure|null $column): static
    {
        $this->avatarColumn = $column;

        return $this;
    }

    public function nameColumn(string|Closure $column): static
    {
        $this->nameColumn = $column;

        return $this;
    }

    public function emailColumn(string|Closure|null $column): static
    {
        $this->emailColumn = $column;

        return $this;
    }

    public function verificationColumn(string|Closure|null $column): static
    {
        $this->verificationColumn = $column;

        return $this;
    }

    public function getAvatarUrlUsing(?Closure $callback): static
    {
        $this->getAvatarUrlUsing = $callback;

        return $this;
    }

    public function getNameUsing(?Closure $callback): static
    {
        $this->getNameUsing = $callback;

        return $this;
    }

    public function getEmailUsing(?Closure $callback): static
    {
        $this->getEmailUsing = $callback;

        return $this;
    }

    public function isVerifiedUsing(?Closure $callback): static
    {
        $this->isVerifiedUsing = $callback;

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

    /**
     * @return array<array<string, mixed>>
     */
    public function getOptionsForJs(): array
    {
        if ($this->getUserModel() !== null) {
            return $this->transformOptionsForJs($this->getDefaultSuggestions());
        }

        return parent::getOptionsForJs();
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getInitialOptionsForJs(): array
    {
        if ($this->getUserModel() !== null) {
            return [];
        }

        return $this->getOptionsForJs();
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getOptionLabelsForJs(): array
    {
        $state = $this->getState();

        if (! is_array($state) || $state === []) {
            return [];
        }

        $shapes = $this->resolveOptionShapesForValues($state);
        $options = [];

        foreach ($state as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $shape = $shapes[(string) $value] ?? null;

            if ($shape === null) {
                continue;
            }

            $options[$value] = $shape;
        }

        return $this->transformOptionsForJs($options);
    }

    public function shouldRenderAvatarStackTrigger(): bool
    {
        return false;
    }

    public function shouldRenderMultipleUserTags(): bool
    {
        return $this->isMultiple();
    }

    public function getUserModel(): ?string
    {
        $model = $this->evaluate($this->userModel);

        return is_string($model) && filled($model) ? $model : null;
    }

    public function getNameColumn(): string
    {
        return (string) $this->evaluate($this->nameColumn);
    }

    public function getEmailColumn(): ?string
    {
        $column = $this->evaluate($this->emailColumn);

        return filled($column) ? (string) $column : null;
    }

    public function getAvatarColumn(): ?string
    {
        $column = $this->evaluate($this->avatarColumn);

        return filled($column) ? (string) $column : null;
    }

    public function getVerificationColumn(): ?string
    {
        $column = $this->evaluate($this->verificationColumn);

        return filled($column) ? (string) $column : null;
    }

    /**
     * @return array<string, string>
     */
    public function getWrapperClasses(): array
    {
        $classes = parent::getWrapperClasses();

        $classes['fff-user-select'] = true;

        if ($this->isMultiple()) {
            $classes['fff-user-select--multiple'] = true;
            $classes['fff-select-field--rich-list-trigger'] = true;
        }

        if (! $this->isMultiple()) {
            $classes['fff-user-select--single'] = true;
            $classes['fff-select-field--rich-list-trigger'] = true;
        }

        return $classes;
    }

    /**
     * @param  array<string | int, string | array<string, mixed>>  $options
     * @return list<array<string, mixed>>
     */
    public function transformRichOptionsForJs(array $options): array
    {
        $transformed = [];

        foreach ($options as $value => $label) {
            if (is_array($label) && $this->isOptionGroupArray($label)) {
                $transformed[] = [
                    'label' => (string) $value,
                    'options' => $this->transformRichOptionsForJs($label),
                ];

                continue;
            }

            if (is_array($label) && $this->isUserOptionArray($label)) {
                $shape = $this->recordToOptionArrayFromShape($value, $label);
            } elseif (is_string($label)) {
                $shape = [
                    'label' => $label,
                    'description' => null,
                    'image' => null,
                    'verified' => false,
                    'disabled' => $this->isOptionDisabled($value, $label),
                ];
            } else {
                continue;
            }

            $transformed[] = $this->formatUserOptionForJs($value, $shape);
        }

        return $transformed;
    }

    /**
     * @param  array{
     *     label: string,
     *     description?: ?string,
     *     image?: ?string,
     *     verified?: bool,
     *     disabled?: bool,
     * }  $shape
     * @return array{
     *     name: string,
     *     email: ?string,
     *     avatarUrl: ?string,
     *     verified: bool,
     *     initials: string,
     * }
     */
    public function userShapeToClientPayload(array $shape): array
    {
        $name = (string) $shape['label'];

        return [
            'name' => $name,
            'email' => filled($shape['description'] ?? null) ? (string) $shape['description'] : null,
            'avatarUrl' => filled($shape['image'] ?? null) ? (string) $shape['image'] : null,
            'verified' => (bool) ($shape['verified'] ?? false),
            'initials' => $this->getUserSelectInitials($name),
        ];
    }

    /**
     * @param  array{
     *     label: string,
     *     description?: ?string,
     *     image?: ?string,
     *     verified?: bool,
     *     disabled?: bool,
     * }  $shape
     * @return array{
     *     value: string,
     *     label: string,
     *     user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string},
     *     userName: string,
     *     isDisabled: bool,
     *     fffClientRender: true,
     * }
     */
    public function formatUserOptionForJs(string|int $value, array $shape): array
    {
        $user = $this->userShapeToClientPayload($shape);

        return [
            'value' => (string) $value,
            'label' => $user['name'],
            'user' => $user,
            'userName' => $user['name'],
            'isDisabled' => (bool) ($shape['disabled'] ?? $this->isOptionDisabled($value, $user['name'])),
            'fffClientRender' => true,
        ];
    }

    /**
     * @return array{
     *     value: string|int,
     *     label: string,
     *     description: ?string,
     *     icon: string|BackedEnum|Htmlable|null,
     *     image: ?string,
     *     badge: ?string,
     *     badge_color: ?string,
     *     disabled: bool,
     *     verified: bool,
     * }
     */
    protected function normalizeOption(string|int $value, array|string $label): array
    {
        $normalized = parent::normalizeOption($value, $label);

        if (is_array($label)) {
            $normalized['verified'] = (bool) ($label['verified'] ?? false);
        } else {
            $normalized['verified'] = false;
        }

        return $normalized;
    }

    /**
     * @param  array{
     *     value: string|int,
     *     label: string,
     *     description: ?string,
     *     icon: string|BackedEnum|Htmlable|null,
     *     image: ?string,
     *     badge: ?string,
     *     badge_color: ?string,
     *     disabled: bool,
     *     verified: bool,
     * }  $option
     */
    protected function renderRichOptionLabel(array $option, string $layout = 'list'): string
    {
        return $this->renderUserOption([
            'label' => $option['label'],
            'description' => $option['description'],
            'image' => $option['image'],
            'verified' => $option['verified'],
        ], layout: $layout);
    }

    /**
     * @param  array{
     *     value: string|int,
     *     label: string,
     *     description: ?string,
     *     icon: string|BackedEnum|Htmlable|null,
     *     image: ?string,
     *     badge: ?string,
     *     badge_color: ?string,
     *     disabled: bool,
     *     verified: bool,
     * }  $option
     */
    protected function formatOptionLabelForJs(array $option, bool $compact = false): string
    {
        if ($compact && $this->isMultiple()) {
            return $this->renderUserOption([
                'label' => $option['label'],
                'description' => $option['description'],
                'image' => $option['image'],
                'verified' => $option['verified'],
            ], layout: 'list');
        }

        return parent::formatOptionLabelForJs($option, $compact);
    }

    /**
     * @param  array{
     *     label: string,
     *     description?: ?string,
     *     image?: ?string,
     *     verified?: bool,
     * }  $option
     */
    public function renderUserOption(array $option, string $layout = 'list'): string
    {
        /** @var View $view */
        $view = view('filament-flex-fields::forms.components.partials.user-select-option', [
            'label' => $option['label'],
            'description' => $option['description'] ?? null,
            'image' => filled($option['image'] ?? null) ? (string) $option['image'] : null,
            'verified' => (bool) ($option['verified'] ?? false),
            'initials' => $this->getUserSelectInitials($option['label']),
            'layout' => $layout,
            'iconSize' => match ($this->getSize()) {
                'sm' => IconSize::Small,
                'lg' => IconSize::Large,
                default => IconSize::Medium,
            },
        ]);

        return $view->render();
    }

    /**
     * @return array{
     *     triggerHtml: ?string,
     *     tagsHtml: ?string,
     *     entries: list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>,
     * }
     */
    public function getInitialUserSelectDisplay(): array
    {
        if (! $this->isMultiple()) {
            return [
                'triggerHtml' => null,
                'tagsHtml' => null,
                'entries' => [],
            ];
        }

        $users = $this->resolveSelectedUsersForDisplay();

        if ($users === []) {
            return [
                'triggerHtml' => null,
                'tagsHtml' => null,
                'entries' => [],
            ];
        }

        $triggerHtml = count($users) === 1
            ? $this->renderUserOption([
                'label' => $users[0]['user']['name'],
                'description' => $users[0]['user']['email'],
                'image' => $users[0]['user']['avatarUrl'],
                'verified' => $users[0]['user']['verified'],
            ], layout: 'trigger')
            : $this->renderMultipleTriggerNamesHtml($users);

        $tagsHtml = count($users) < 2
            ? null
            : $this->renderSelectedUserTagsHtml($users);

        return [
            'triggerHtml' => $triggerHtml,
            'tagsHtml' => $tagsHtml,
            'entries' => $users,
        ];
    }

    public function getInitialMultipleTriggerHtml(): ?string
    {
        return $this->getInitialUserSelectDisplay()['triggerHtml'];
    }

    public function getInitialSelectedUserTagsHtml(): ?string
    {
        return $this->getInitialUserSelectDisplay()['tagsHtml'];
    }

    /**
     * @return list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>
     */
    public function getInitialSelectedUserEntriesForJs(): array
    {
        if ($this->isMultiple()) {
            return $this->getInitialUserSelectDisplay()['entries'];
        }

        $state = $this->resolveStateForItemCardTrigger();

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        if (blank($state)) {
            return [];
        }

        $shape = $this->resolveOptionShapeForValue($state);

        if ($shape === null) {
            return [];
        }

        return [
            $this->buildSelectedUserDisplayEntry($state, $shape),
        ];
    }

    /**
     * @param  list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>  $users
     */
    protected function renderMultipleTriggerNamesHtml(array $users): string
    {
        $names = array_map(
            fn (array $user): string => $user['user']['name'],
            $users,
        );

        return '<span class="fff-user-select__trigger-names" data-fff-user-select-names="'
            .e(implode("\n", $names))
            .'">'
            .e(implode(', ', $names))
            .'</span>';
    }

    /**
     * @param  list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>  $users
     */
    protected function renderSelectedUserTagsHtml(array $users): string
    {
        $html = '<div class="fff-user-select__selected-tags" data-fff-user-select-tags>';

        foreach ($users as $user) {
            $html .= $this->renderUserSelectedTag($user['value'], [
                'label' => $user['user']['name'],
                'description' => $user['user']['email'],
                'image' => $user['user']['avatarUrl'],
                'verified' => $user['user']['verified'],
            ]);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @return list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>
     */
    public function resolveSelectedUsersForDisplay(): array
    {
        if ($this->cachedSelectedUsersForDisplay !== null) {
            return $this->cachedSelectedUsersForDisplay;
        }

        if (! $this->isMultiple()) {
            $this->cachedSelectedUsersForDisplay = [];

            return $this->cachedSelectedUsersForDisplay;
        }

        $state = $this->resolveStateForItemCardTrigger();

        if (! is_array($state) || $state === []) {
            $this->cachedSelectedUsersForDisplay = [];

            return $this->cachedSelectedUsersForDisplay;
        }

        $shapes = $this->resolveOptionShapesForValues($state);
        $users = [];

        foreach ($state as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $shape = $shapes[(string) $value] ?? null;

            if ($shape === null) {
                continue;
            }

            $users[] = $this->buildSelectedUserDisplayEntry($value, $shape);
        }

        $this->cachedSelectedUsersForDisplay = $users;

        return $this->cachedSelectedUsersForDisplay;
    }

    /**
     * @param  array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     * }  $shape
     * @return array{
     *     value: string,
     *     user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string},
     * }
     */
    protected function buildSelectedUserDisplayEntry(string|int $value, array $shape): array
    {
        return [
            'value' => (string) $value,
            'user' => $this->userShapeToClientPayload($shape),
        ];
    }

    /**
     * @param  array{
     *     label: string,
     *     description?: ?string,
     *     image?: ?string,
     *     verified?: bool,
     * }  $option
     */
    public function renderUserSelectedTag(string|int $value, array $option): string
    {
        $isDisabled = false;

        try {
            $isDisabled = $this->isDisabled();
        } catch (\Throwable) {
            $isDisabled = false;
        }

        /** @var View $view */
        $view = view('filament-flex-fields::forms.components.partials.user-select-selected-tag', [
            'value' => (string) $value,
            'name' => $option['label'],
            'tagHtml' => $this->renderUserOption($option, layout: 'tag'),
            'removable' => ! $isDisabled,
        ]);

        return $view->render();
    }

    /**
     * @return ?array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     * }
     */
    protected function resolveOptionShapeForValue(mixed $value): ?array
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return $this->resolveOptionShapesForValues([$value])[(string) $value] ?? null;
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return array<string, array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     * }>
     */
    public function resolveOptionShapesForValues(array $values): array
    {
        $shapes = [];
        $missingValues = [];
        $options = $this->getOptions();

        foreach ($values as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $key = (string) $value;

            if (isset($this->resolvedOptionShapeCache[$key])) {
                $shapes[$key] = $this->resolvedOptionShapeCache[$key];

                continue;
            }

            $label = $this->findOptionLabel($options, $value);

            if (is_array($label)) {
                $shape = [
                    'label' => (string) ($label['label'] ?? $value),
                    'description' => filled($label['description'] ?? null) ? (string) $label['description'] : null,
                    'image' => filled($label['image'] ?? null) ? (string) $label['image'] : null,
                    'verified' => (bool) ($label['verified'] ?? false),
                ];

                $shapes[$key] = $shape;
                $this->resolvedOptionShapeCache[$key] = $shape;

                continue;
            }

            if (is_string($label) && filled($label)) {
                $shape = [
                    'label' => $label,
                    'description' => null,
                    'image' => null,
                    'verified' => false,
                ];

                $shapes[$key] = $shape;
                $this->resolvedOptionShapeCache[$key] = $shape;

                continue;
            }

            $missingValues[] = $value;
        }

        if ($missingValues !== [] && ($this->hasRelationship() || $this->getUserModel() !== null)) {
            $this->resolveRecordsForValues($missingValues);

            foreach ($missingValues as $value) {
                $key = (string) $value;
                $record = $this->resolvedRecordCache[$key] ?? null;

                if (! $record instanceof Model) {
                    continue;
                }

                $shape = $this->recordToOptionArray($record);
                $shapes[$key] = $shape;
                $this->resolvedOptionShapeCache[$key] = $shape;
            }
        }

        return $shapes;
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    protected function resolveRecordsForValues(array $values): void
    {
        $uncachedValues = [];

        foreach ($values as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $key = (string) $value;

            if (! isset($this->resolvedRecordCache[$key])) {
                $uncachedValues[] = $value;
            }
        }

        if ($uncachedValues === []) {
            return;
        }

        if ($this->hasRelationship()) {
            $this->resolveRelationshipRecordsForValues($uncachedValues);

            return;
        }

        $modelClass = $this->getUserModel();

        if ($modelClass === null) {
            return;
        }

        $keyName = $this->resolveModelKeyName();

        $this->getQueryResult(
            $this->buildModelQuery()->whereIn($keyName, $uncachedValues)
        )->each(function (Model $record) use ($keyName): void {
            $key = (string) $record->getAttribute($keyName);
            $this->resolvedRecordCache[$key] = $record;
        });
    }

    /**
     * @param  list<mixed>  $values
     */
    protected function resolveRelationshipRecordsForValues(array $values): void
    {
        $relationship = Relation::noConstraints(fn () => $this->getRelationship());
        $relationshipQuery = app(RelationshipJoiner::class)
            ->prepareQueryForNoConstraints($relationship);

        $qualifiedRelatedKeyName = $this->getQualifiedRelatedKeyNameForRelationship($relationship);

        $relationshipQuery->whereIn($qualifiedRelatedKeyName, $values);

        if ($this->modifyQueryUsing !== null) {
            $relationshipQuery = $this->evaluate($this->modifyQueryUsing, [
                'query' => $relationshipQuery,
                'search' => null,
            ]) ?? $relationshipQuery;
        }

        $keyName = Str::afterLast($qualifiedRelatedKeyName, '.');

        $this->getQueryResult($relationshipQuery)
            ->each(function (Model $record) use ($keyName): void {
                $key = (string) $record->getAttribute($keyName);
                $this->resolvedRecordCache[$key] = $record;
            });
    }

    /**
     * @return array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     * }
     */
    public function recordToOptionArray(Model $record): array
    {
        return [
            'label' => $this->resolveName($record),
            'description' => $this->resolveEmail($record),
            'image' => $this->resolveAvatarUrl($record),
            'verified' => $this->resolveIsVerified($record),
        ];
    }

    /**
     * @param  array<string, mixed>  $shape
     * @return array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     *     disabled?: bool,
     * }
     */
    protected function recordToOptionArrayFromShape(string|int $value, array $shape): array
    {
        return [
            'label' => (string) ($shape['label'] ?? $value),
            'description' => filled($shape['description'] ?? null) ? (string) $shape['description'] : null,
            'image' => filled($shape['image'] ?? null) ? (string) $shape['image'] : null,
            'verified' => (bool) ($shape['verified'] ?? false),
            'disabled' => (bool) ($shape['disabled'] ?? $this->isOptionDisabled($value, (string) ($shape['label'] ?? $value))),
        ];
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return array<int|string, string>
     */
    public function resolveOptionLabelsForValues(array $values): array
    {
        $labels = [];
        $shapes = $this->resolveOptionShapesForValues($values);

        foreach ($values as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $shape = $shapes[(string) $value] ?? null;

            if ($shape === null) {
                $labels[$value] = (string) $value;

                continue;
            }

            $labels[$value] = $shape['label'];
        }

        return $labels;
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function getDefaultSuggestions(): array
    {
        if ($this->getUserModel() === null) {
            return [];
        }

        return $this->searchRecords(null);
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function searchRecords(?string $search): array
    {
        if ($this->getUserModel() === null) {
            return [];
        }

        if (blank($search)) {
            return $this->fetchDefaultSuggestions();
        }

        $term = trim($search);

        if (mb_strlen($term) < $this->getMinSearchLength()) {
            return [];
        }

        $cacheKey = $this->searchCacheKey($term);

        if (isset($this->searchResultsCache[$cacheKey])) {
            return $this->searchResultsCache[$cacheKey];
        }

        $limit = $this->getOptionsLimit();
        $query = $this->buildModelQuery();
        $this->applySearchToQuery($query, $term);
        $this->applySearchRelevanceOrdering($query, $term);

        $results = $this->mapQueryRecordsToOptions(
            $this->getQueryResult($query->limit($limit)),
        );

        $tokens = $this->extractSearchTokens($term);

        if (count($results) < $limit && count($tokens) > 1) {
            $fallbackQuery = $this->buildModelQuery();
            $this->applyMultiTokenSearchToQuery($fallbackQuery, $tokens, array_keys($results));
            $this->applySearchRelevanceOrdering($fallbackQuery, $term);

            $additionalResults = $this->mapQueryRecordsToOptions(
                $this->getQueryResult($fallbackQuery->limit($limit - count($results))),
            );

            $results = $results + $additionalResults;
        }

        return $this->searchResultsCache[$cacheKey] = $results;
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    protected function fetchDefaultSuggestions(): array
    {
        $cacheKey = $this->searchCacheKey(null);

        if (isset($this->searchResultsCache[$cacheKey])) {
            return $this->searchResultsCache[$cacheKey];
        }

        $keyName = $this->resolveModelKeyName();

        $results = $this->mapQueryRecordsToOptions(
            $this->getQueryResult(
                $this->buildModelQuery()
                    ->orderByDesc($keyName)
                    ->limit($this->getDefaultSuggestionsLimit())
            ),
        );

        return $this->searchResultsCache[$cacheKey] = $results;
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return array<int|string, array<string, mixed>>
     */
    protected function mapQueryRecordsToOptions($records): array
    {
        $keyName = $this->resolveModelKeyName();

        return $records
            ->mapWithKeys(fn (Model $record): array => [
                $record->getAttribute($keyName) => $this->recordToOptionArray($record),
            ])
            ->all();
    }

    protected function searchCacheKey(?string $search): string
    {
        return hash('xxh128', implode('|', [
            $this->getUserModel() ?? '',
            $search ?? '',
            (string) $this->getOptionsLimit(),
            (string) $this->getDefaultSuggestionsLimit(),
            $this->getNameColumn(),
            $this->getEmailColumn() ?? '',
        ]));
    }

    /**
     * @return list<string>
     */
    protected function extractSearchTokens(string $search): array
    {
        $tokens = preg_split('/\s+/u', trim($search)) ?: [];

        return array_values(array_filter(
            $tokens,
            fn (string $token): bool => mb_strlen($token) >= $this->getMinSearchLength(),
        ));
    }

    protected function qualifySearchColumn(Builder $query, string $column): string
    {
        if (str_contains($column, '.')) {
            return $column;
        }

        return $query->qualifyColumn($column);
    }

    protected function needsFullModelForResolvers(): bool
    {
        return $this->getNameUsing !== null
            || $this->getEmailUsing !== null
            || $this->getAvatarUrlUsing !== null
            || $this->isVerifiedUsing !== null;
    }

    protected function restrictModelQueryColumns(Builder $query): Builder
    {
        if ($this->needsFullModelForResolvers()) {
            return $query;
        }

        $columns = array_values(array_unique(array_filter([
            $this->resolveModelKeyName(),
            $this->getNameColumn(),
            $this->getEmailColumn(),
            $this->getAvatarColumn(),
            $this->getVerificationColumn(),
        ], fn (?string $column): bool => filled($column))));

        if ($columns === []) {
            return $query;
        }

        return $query->select($columns);
    }

    protected function applySearchRelevanceOrdering(Builder $query, string $search): void
    {
        $term = trim($search);
        $escaped = addcslashes($term, '%_\\');
        $prefixPattern = $escaped.'%';
        $nameColumn = $this->qualifySearchColumn($query, $this->getNameColumn());
        $emailColumn = $this->getEmailColumn();

        if ($emailColumn === null) {
            $query
                ->orderByRaw("CASE WHEN {$nameColumn} LIKE ? THEN 0 ELSE 1 END", [$prefixPattern])
                ->orderBy($nameColumn);

            return;
        }

        $qualifiedEmailColumn = $this->qualifySearchColumn($query, $emailColumn);

        $query
            ->orderByRaw(
                "CASE WHEN {$nameColumn} LIKE ? THEN 0 WHEN {$qualifiedEmailColumn} LIKE ? THEN 1 ELSE 2 END",
                [$prefixPattern, $prefixPattern],
            )
            ->orderBy($nameColumn);
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<int|string>  $excludeValues
     */
    protected function applyMultiTokenSearchToQuery(Builder $query, array $tokens, array $excludeValues): void
    {
        $nameColumn = $this->qualifySearchColumn($query, $this->getNameColumn());
        $emailColumn = $this->getEmailColumn();
        $qualifiedEmailColumn = $emailColumn !== null
            ? $this->qualifySearchColumn($query, $emailColumn)
            : null;

        $query->where(function (Builder $inner) use ($nameColumn, $qualifiedEmailColumn, $tokens): void {
            foreach ($tokens as $token) {
                $escaped = addcslashes($token, '%_\\');
                $prefixPattern = $escaped.'%';
                $wordPattern = '% '.$escaped.'%';

                $inner->where(function (Builder $tokenQuery) use ($nameColumn, $qualifiedEmailColumn, $prefixPattern, $wordPattern, $escaped): void {
                    $tokenQuery
                        ->where($nameColumn, 'like', $prefixPattern)
                        ->orWhere($nameColumn, 'like', $wordPattern);

                    if ($qualifiedEmailColumn !== null) {
                        $tokenQuery
                            ->orWhere($qualifiedEmailColumn, 'like', $prefixPattern)
                            ->orWhere($qualifiedEmailColumn, 'like', $escaped.'@%');
                    }
                });
            }
        });

        if ($excludeValues !== []) {
            $query->whereNotIn($this->resolveModelKeyName(), $excludeValues);
        }
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

            $record = $component->resolveRecordForValue($state);

            if (! $record instanceof Model) {
                return null;
            }

            return $component->renderUserOption(
                $component->recordToOptionArray($record),
                layout: 'trigger',
            );
        });

    }

    protected function buildModelQuery(): Builder
    {
        $modelClass = $this->getUserModel();

        if ($modelClass === null || ! class_exists($modelClass)) {
            throw new InvalidArgumentException('UserSelect requires a valid Eloquent model class via optionModel().');
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException("UserSelect model [{$modelClass}] must extend [".Model::class.'].');
        }

        /** @var Builder $query */
        $query = $modelClass::query();

        if ($this->modifyQueryUsing !== null) {
            $query = $this->evaluate($this->modifyQueryUsing, [
                'query' => $query,
            ]) ?? $query;
        }

        return $this->restrictModelQueryColumns($query);
    }

    protected function applySearchToQuery(Builder $query, string $search): void
    {
        if ($this->applySearchUsing !== null) {
            $this->evaluate($this->applySearchUsing, [
                'query' => $query,
                'search' => trim($search),
            ]);

            return;
        }

        $term = trim($search);
        $nameColumn = $this->qualifySearchColumn($query, $this->getNameColumn());
        $emailColumn = $this->getEmailColumn();
        $qualifiedEmailColumn = $emailColumn !== null
            ? $this->qualifySearchColumn($query, $emailColumn)
            : null;
        $escaped = addcslashes($term, '%_\\');
        $prefixPattern = $escaped.'%';

        $query->where(function (Builder $inner) use ($nameColumn, $qualifiedEmailColumn, $prefixPattern, $escaped): void {
            $inner->where($nameColumn, 'like', $prefixPattern);

            if ($qualifiedEmailColumn !== null) {
                $inner
                    ->orWhere($qualifiedEmailColumn, 'like', $prefixPattern)
                    ->orWhere($qualifiedEmailColumn, 'like', $escaped.'@%');
            }
        });
    }

    protected function resolveRecordForValue(mixed $value): ?Model
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        $key = (string) $value;

        if (isset($this->resolvedRecordCache[$key])) {
            return $this->resolvedRecordCache[$key];
        }

        if ($this->hasRelationship()) {
            $record = $this->evaluate($this->getSelectedRecordUsing, [
                'state' => $value,
            ]);

            if ($record instanceof Model) {
                $this->resolvedRecordCache[$key] = $record;
            }

            return $record instanceof Model ? $record : null;
        }

        $this->resolveRecordsForValues([$value]);

        return $this->resolvedRecordCache[$key] ?? null;
    }

    protected function resolveModelKeyName(): string
    {
        $modelClass = $this->getUserModel();

        if ($modelClass === null) {
            return 'id';
        }

        /** @var Model $model */
        $model = new $modelClass;

        return $model->getKeyName();
    }

    protected function resolveName(Model $record): string
    {
        if ($this->getNameUsing !== null) {
            return (string) $this->evaluate($this->getNameUsing, ['record' => $record]);
        }

        return (string) data_get($record, $this->getNameColumn());
    }

    protected function resolveEmail(Model $record): ?string
    {
        if ($this->getEmailUsing !== null) {
            $email = $this->evaluate($this->getEmailUsing, ['record' => $record]);

            return filled($email) ? (string) $email : null;
        }

        $column = $this->getEmailColumn();

        if ($column === null) {
            return null;
        }

        $email = data_get($record, $column);

        return filled($email) ? (string) $email : null;
    }

    protected function resolveAvatarUrl(Model $record): ?string
    {
        if ($this->getAvatarUrlUsing !== null) {
            $url = $this->evaluate($this->getAvatarUrlUsing, ['record' => $record]);

            return filled($url) ? (string) $url : null;
        }

        $column = $this->getAvatarColumn();

        if ($column === null) {
            return null;
        }

        $url = data_get($record, $column);

        return filled($url) ? (string) $url : null;
    }

    protected function resolveIsVerified(Model $record): bool
    {
        if ($this->isVerifiedUsing !== null) {
            return (bool) $this->evaluate($this->isVerifiedUsing, ['record' => $record]);
        }

        $column = $this->getVerificationColumn();

        if ($column === null) {
            return false;
        }

        $value = data_get($record, $column);

        if (is_bool($value)) {
            return $value;
        }

        return filled($value);
    }

    /**
     * @param  array<string, mixed>  $option
     */
    protected function isUserOptionArray(array $option): bool
    {
        return array_key_exists('label', $option)
            && (
                array_key_exists('verified', $option)
                || array_key_exists('image', $option)
                || array_key_exists('description', $option)
            );
    }

    public function getInitialTriggerLabel(): ?string
    {
        if ($this->isMultiple()) {
            return $this->getInitialMultipleTriggerHtml();
        }

        if ($this->isNative() || $this->getVariant() === 'item-card') {
            return null;
        }

        $state = $this->resolveStateForItemCardTrigger();

        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        if (blank($state)) {
            return (string) ($this->getPlaceholder() ?? '');
        }

        $label = $this->findOptionLabel($this->getOptions(), $state);

        if (is_array($label)) {
            return $this->renderUserOption(
                $this->recordToOptionArrayFromShape($state, $label),
                layout: 'trigger',
            );
        }

        if ($this->getUserModel() !== null || $this->hasRelationship()) {
            $record = $this->resolveRecordForValue($state);

            if ($record instanceof Model) {
                return $this->renderUserOption(
                    $this->recordToOptionArray($record),
                    layout: 'trigger',
                );
            }
        }

        return parent::getInitialTriggerLabel();
    }

    /**
     * @return list<array{value: string, label: string, name: string}>
     */
    public function getInitialTriggerBadges(): array
    {
        return [];
    }

    /**
     * @return list<array{value: string, label: string, name: string}>
     */
    public function getInitialTriggerBadgesLegacy(): array
    {
        return [];
    }

    protected function resolveDisplayNameForValue(mixed $value): ?string
    {
        $shape = $this->resolveOptionShapeForValue($value);

        return $shape['label'] ?? null;
    }

    public function getUserSelectInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if ($parts === []) {
            return '';
        }

        if (count($parts) === 1) {
            return Str::upper(Str::substr($parts[0], 0, 2));
        }

        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= Str::upper(Str::substr($part, 0, 1));
        }

        return $initials;
    }

    /**
     * @param  Builder<*>|Relation<*, *, *>|\Illuminate\Database\Query\Builder  $query
     * @return Collection<int, Model>
     */
    protected function getQueryResult(Builder|Relation|\Illuminate\Database\Query\Builder $query): Collection
    {
        /** @var UserSelectQueryCache $cacheStore */
        $cacheStore = app(UserSelectQueryCache::class);
        $cacheKey = hash('xxh128', $query->toSql().'|'.serialize($query->getBindings()));

        if (isset($cacheStore->cache[$cacheKey])) {
            return $cacheStore->cache[$cacheKey];
        }

        /** @var Collection<int, Model> $result */
        $result = $query->get();

        return $cacheStore->cache[$cacheKey] = $result;
    }
}
