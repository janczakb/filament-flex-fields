<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\UserSelect;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin UserSelect
 */
trait InteractsWithUserSelectPresentation
{
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

    public function shouldRenderAvatarStackTrigger(): bool
    {
        return false;
    }

    public function shouldRenderMultipleUserTags(): bool
    {
        return $this->isMultiple();
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
                $shape = $this->recordMapper()->recordToOptionArrayFromShape($value, $label);
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
        return $this->optionPresenter()->userShapeToClientPayload($shape);
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
        return $this->optionPresenter()->renderUserOption([
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
            return $this->optionPresenter()->renderUserOption([
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
        return $this->optionPresenter()->renderUserOption($option, $layout);
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
        return $this->optionPresenter()->initialDisplay();
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

        $shape = $this->shapeResolver()->resolveOptionShapeForValue($state);

        if ($shape === null) {
            return [];
        }

        return [
            $this->optionPresenter()->buildSelectedUserDisplayEntry($state, $shape),
        ];
    }

    /**
     * @return list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>
     */
    public function resolveSelectedUsersForDisplay(): array
    {
        if ($this->runtimeState()->cachedSelectedUsersForDisplay !== null) {
            return $this->runtimeState()->cachedSelectedUsersForDisplay;
        }

        if (! $this->isMultiple()) {
            $this->runtimeState()->cachedSelectedUsersForDisplay = [];

            return $this->runtimeState()->cachedSelectedUsersForDisplay;
        }

        $state = $this->resolveStateForItemCardTrigger();

        if (! is_array($state) || $state === []) {
            $this->runtimeState()->cachedSelectedUsersForDisplay = [];

            return $this->runtimeState()->cachedSelectedUsersForDisplay;
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

            $users[] = $this->optionPresenter()->buildSelectedUserDisplayEntry($value, $shape);
        }

        $this->runtimeState()->cachedSelectedUsersForDisplay = $users;

        return $this->runtimeState()->cachedSelectedUsersForDisplay;
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
        return $this->optionPresenter()->renderUserSelectedTag($value, $option);
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
        return $this->shapeResolver()->resolveOptionShapesForValues($values);
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
        return $this->recordMapper()->recordToOptionArray($record);
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return array<int|string, string>
     */
    public function resolveOptionLabelsForValues(array $values): array
    {
        return $this->shapeResolver()->resolveOptionLabelsForValues($values);
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
                $this->recordMapper()->recordToOptionArrayFromShape($state, $label),
                layout: 'trigger',
            );
        }

        if ($this->getUserModel() !== null || $this->hasRelationship()) {
            $record = $this->queryEngine()->resolveRecordForValue($state);

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
        $shape = $this->shapeResolver()->resolveOptionShapeForValue($value);

        return $shape['label'] ?? null;
    }

    public function getUserSelectInitials(string $name): string
    {
        return $this->optionPresenter()->initialsForName($name);
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
}
