<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Concerns\HasAffixes;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Contracts\HasAffixActions;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class FlexTextareaField extends Textarea implements HasAffixActions
{
    use HasAffixes;
    use HasControlSize;
    use HasFieldFocusOutline;

    protected string $view = 'filament-flex-fields::forms.components.flex-textarea-field';

    protected string|Closure $variant = 'primary';

    protected bool|Closure $showCharacterCounter = false;

    protected bool|Closure $animatedAutosize = true;

    protected string|Closure|null $maxHeight = '24rem';

    protected string|Closure|null $footer = null;

    protected bool|Closure $speechDictation = false;

    protected string|Closure|null $speechDictationLanguage = null;

    protected string|Closure $speechDictationLabel = 'Speak';

    protected bool|Closure $emojiPicker = false;

    protected string|Closure|null $emojiPickerLocale = null;

    protected string|Closure $emojiPickerLabel = 'Insert emoji';

    protected string|BackedEnum|Htmlable|Closure|null $emojiIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $microphoneIcon = null;

    /**
     * @var list<string>
     */
    protected array $submitActionNames = [];

    /**
     * @var array<int, array<string, mixed>> | Closure
     */
    protected array|Closure $toolbarSelects = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->autosize();
        $this->rows(1);
        $this->disableGrammarly();
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function characterCounter(bool|Closure $condition = true): static
    {
        $this->showCharacterCounter = $condition;

        return $this;
    }

    public function animatedAutosize(bool|Closure $condition = true): static
    {
        $this->animatedAutosize = $condition;

        return $this;
    }

    public function maxHeight(string|Closure|null $height): static
    {
        $this->maxHeight = $height;

        return $this;
    }

    public function footer(string|Closure|null $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    public function speechDictation(bool|Closure $condition = true): static
    {
        $this->speechDictation = $condition;

        return $this;
    }

    /**
     * Override speech recognition language. Leave unset to use the browser default locale.
     */
    public function speechDictationLanguage(string|Closure|null $language): static
    {
        $this->speechDictationLanguage = $language;

        return $this;
    }

    public function speechDictationLabel(string|Closure $label): static
    {
        $this->speechDictationLabel = $label;

        return $this;
    }

    public function emojiPicker(bool|Closure $condition = true): static
    {
        $this->emojiPicker = $condition;

        return $this;
    }

    /**
     * Emoji picker locale (e.g. pl, en). Leave unset to use the browser language.
     */
    public function emojiPickerLocale(string|Closure|null $locale): static
    {
        $this->emojiPickerLocale = $locale;

        return $this;
    }

    public function emojiPickerLabel(string|Closure $label): static
    {
        $this->emojiPickerLabel = $label;

        return $this;
    }

    public function toolbarAction(Action|Closure $action): static
    {
        return $this->prefixAction($action);
    }

    /**
     * Pill-style toolbar dropdown that keeps the active option visible on the trigger.
     *
     * @param  array<string, string> | Closure  $options
     */
    public function toolbarSelect(
        string $statePath,
        array|Closure $options,
        string|BackedEnum|Htmlable|Closure|null $icon = null,
        string|Closure|null $placeholder = null,
    ): static {
        $this->toolbarSelects = [
            ...Arr::wrap($this->evaluate($this->toolbarSelects)),
            [
                'statePath' => $statePath,
                'options' => $options,
                'icon' => $icon,
                'placeholder' => $placeholder ?? 'Select',
            ],
        ];

        return $this;
    }

    /**
     * @param  array<Action | ActionGroup | Closure>  $actions
     */
    public function toolbarActions(array $actions): static
    {
        return $this->prefixActions($actions);
    }

    public function cachePrefixActions(): array
    {
        $this->cachedPrefixActions = [];

        foreach ($this->prefixActions as $prefixAction) {
            foreach (Arr::wrap($this->evaluate($prefixAction)) as $action) {
                if ($action instanceof ActionGroup) {
                    $this->cachedPrefixActions['group_'.spl_object_id($action)] = $this->prepareActionGroup(
                        $action
                            ->defaultSize(Size::Small)
                            ->defaultTriggerView(Action::ICON_BUTTON_VIEW),
                    );

                    continue;
                }

                if (! $action instanceof Action) {
                    continue;
                }

                $this->cachedPrefixActions[$action->getName()] = $this->prepareAction(
                    $action
                        ->defaultSize(Size::Small)
                        ->defaultView(Action::ICON_BUTTON_VIEW),
                );
            }
        }

        return $this->cachedPrefixActions;
    }

    public function cacheSuffixActions(): array
    {
        $this->cachedSuffixActions = [];

        foreach ($this->suffixActions as $suffixAction) {
            foreach (Arr::wrap($this->evaluate($suffixAction)) as $action) {
                if (! $action instanceof Action) {
                    continue;
                }

                $this->cachedSuffixActions[$action->getName()] = $this->prepareSubmitAction(
                    $this->prepareAction(
                        $action
                            ->defaultSize(Size::Small)
                            ->defaultView(Action::ICON_BUTTON_VIEW)
                            ->color('primary'),
                    ),
                );
            }
        }

        return $this->cachedSuffixActions;
    }

    public function submitAction(Action|Closure|null $action): static
    {
        if ($action === null) {
            return $this;
        }

        if ($action instanceof Action) {
            $this->submitActionNames[] = $action->getName();
        }

        return $this->suffixAction(function (FlexTextareaField $component) use ($action) {
            $resolved = $component->evaluate($action);

            if ($resolved instanceof Action) {
                $component->submitActionNames[] = $resolved->getName();
            }

            return $resolved;
        });
    }

    public function prepareActionGroup(ActionGroup $group): ActionGroup
    {
        return parent::prepareActionGroup($group)
            ->dropdownTeleport(false)
            ->extraDropdownAttributes([
                'class' => 'fff-flex-textarea-action-dropdown',
            ], merge: true);
    }

    protected function prepareSubmitAction(Action $action): Action
    {
        return $action
            ->disabled(fn (FlexTextareaField $component): bool => $component->isSubmitDisabled())
            ->extraAttributes([
                'x-bind:disabled' => '!canSubmit',
            ], merge: true);
    }

    public function isSubmitDisabled(?string $state = null): bool
    {
        if ($state !== null) {
            return blank(trim($state));
        }

        return blank(trim((string) ($this->getState() ?? '')));
    }

    /**
     * @return list<string>
     */
    public function getSubmitActionNames(): array
    {
        return array_values(array_unique($this->submitActionNames));
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat', 'soft'], true)) {
            throw new InvalidArgumentException("Flex textarea variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function shouldShowCharacterCounter(): bool
    {
        return (bool) $this->evaluate($this->showCharacterCounter);
    }

    public function shouldAnimateAutosize(): bool
    {
        return (bool) $this->evaluate($this->animatedAutosize);
    }

    public function getMaxHeight(): ?string
    {
        $height = $this->evaluate($this->maxHeight);

        return filled($height) ? (string) $height : null;
    }

    public function getFooter(): ?string
    {
        $footer = $this->evaluate($this->footer);

        return filled($footer) ? (string) $footer : null;
    }

    public function shouldEnableSpeechDictation(): bool
    {
        return (bool) $this->evaluate($this->speechDictation);
    }

    public function getSpeechDictationLanguage(): ?string
    {
        $language = $this->evaluate($this->speechDictationLanguage);

        return filled($language) ? (string) $language : null;
    }

    public function getSpeechDictationLabel(): string
    {
        return (string) $this->evaluate($this->speechDictationLabel);
    }

    public function shouldEnableEmojiPicker(): bool
    {
        return (bool) $this->evaluate($this->emojiPicker);
    }

    public function getEmojiPickerLocale(): ?string
    {
        $locale = $this->evaluate($this->emojiPickerLocale);

        return filled($locale) ? (string) $locale : null;
    }

    public function getEmojiPickerLabel(): string
    {
        return (string) $this->evaluate($this->emojiPickerLabel);
    }

    public function emojiIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->emojiIcon = $icon;

        return $this;
    }

    public function microphoneIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->microphoneIcon = $icon;

        return $this;
    }

    public function getEmojiIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->emojiIcon);

        if (filled($icon)) {
            return $icon;
        }

        $configured = config('filament-flex-fields.ui.flex_textarea_emoji_icon');

        return is_string($configured) && filled($configured)
            ? $configured
            : GravityIcon::FaceSmile;
    }

    public function getMicrophoneIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->microphoneIcon);

        if (filled($icon)) {
            return $icon;
        }

        $configured = config('filament-flex-fields.ui.flex_textarea_microphone_icon');

        return is_string($configured) && filled($configured)
            ? $configured
            : GravityIcon::Microphone;
    }

    /**
     * @return list<array{statePath: string, options: array<string, string>, placeholder: string, icon: string|BackedEnum|Htmlable|null, initialValue: mixed, initialLabel: string}>
     */
    public function getToolbarSelects(): array
    {
        return collect(Arr::wrap($this->evaluate($this->toolbarSelects)))
            ->map(function (array $select): array {
                $configuredStatePath = (string) $select['statePath'];
                $statePath = $this->isMountedInContainer()
                    ? $this->resolveRelativeStatePath($configuredStatePath)
                    : $configuredStatePath;

                /** @var array<string, string> $options */
                $options = $this->evaluate($select['options']);

                $placeholder = (string) $this->evaluate($select['placeholder'] ?? 'Select');
                $value = $this->resolveToolbarSelectValue($statePath);

                return [
                    'statePath' => $statePath,
                    'options' => $options,
                    'placeholder' => $placeholder,
                    'icon' => $this->evaluate($select['icon'] ?? null),
                    'initialValue' => $value,
                    'initialLabel' => $options[$value] ?? $placeholder,
                ];
            })
            ->values()
            ->all();
    }

    protected function isMountedInContainer(): bool
    {
        return isset($this->container);
    }

    protected function resolveToolbarSelectValue(string $statePath): mixed
    {
        if (! $this->isMountedInContainer()) {
            return null;
        }

        return data_get($this->getLivewire(), $statePath);
    }

    public function getCharacterLimit(): ?int
    {
        $max = $this->getMaxLength();

        return $max === null ? null : (int) $max;
    }

    public function getInitialHeightRem(): float
    {
        return max((($this->getRows() ?? 1) * 1.5) + 0.25, 2.25);
    }

    public function getRenderedHeightRem(?string $content = null): float
    {
        $minHeight = $this->getInitialHeightRem();
        $content = trim($content ?? '');

        if ($content === '') {
            return $minHeight;
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [''];
        $charsPerLine = match ($this->getSize()) {
            'sm' => 42,
            'lg' => 32,
            default => 38,
        };

        $lineCount = 0;

        foreach ($lines as $line) {
            $lineCount += max(1, (int) ceil(mb_strlen($line) / $charsPerLine));
        }

        return max($minHeight, ($lineCount * 1.5) + 0.25);
    }

    /**
     * @return array<string, string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-flex-textarea-field',
            'fff-flex-textarea-field--'.$this->getSize(),
            'fff-flex-textarea-field--'.$this->getVariant(),
        ];
    }
}
