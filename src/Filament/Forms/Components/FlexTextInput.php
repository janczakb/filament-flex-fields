<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput\Actions\CopyAction;
use Filament\Forms\Components\TextInput\Actions\HidePasswordAction;
use Filament\Forms\Components\TextInput\Actions\ShowPasswordAction;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class FlexTextInput extends TextInput
{
    use HasControlSize;
    use HasFieldFocusOutline;

    protected string $view = 'filament-flex-fields::forms.components.flex-text-input-field';

    protected string|Closure $variant = 'primary';

    protected bool|Closure $speechDictation = false;

    protected string|Closure|null $speechDictationLanguage = null;

    protected string|Closure $speechDictationLabel = 'Speak';

    protected bool|Closure $emojiPicker = false;

    protected string|Closure|null $emojiPickerLocale = null;

    protected string|Closure $emojiPickerLabel = 'Insert emoji';

    protected bool|Closure $showCharacterCounter = false;

    protected bool|Closure $clearable = false;

    protected bool|Closure $loadingIndicator = false;

    protected bool|Closure $passwordStrength = false;

    protected string|Htmlable|Closure|null $verificationStatus = null;

    protected string|BackedEnum|Htmlable|Closure|null $verificationStatusIcon = null;

    protected string|Closure $verificationStatusColor = 'primary';

    /**
     * @var array<int, string>|array<string, string>|Closure|null
     */
    protected array|Closure|null $passwordStrengthLabels = null;

    protected string|BackedEnum|Htmlable|Closure|null $copyIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $showPasswordIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $hidePasswordIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $emojiIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $microphoneIcon = null;

    /**
     * @return list<string>
     */
    public static function defaultPasswordStrengthLabels(): array
    {
        return ['Very weak', 'Weak', 'Fair', 'Good', 'Strong'];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->inlinePrefix();
        $this->inlineSuffix();
    }

    public function email(bool|Closure $condition = true): static
    {
        parent::email($condition);

        $this->inputMode(static function (FlexTextInput $component): ?string {
            return $component->isEmail() ? 'email' : null;
        });

        return $this;
    }

    public function getType(): string
    {
        $type = parent::getType();

        if (in_array($type, ['email', 'number'], true)) {
            return 'text';
        }

        return $type;
    }

    public function prefix(string|Htmlable|Closure|null $label, bool|Closure $isInline = true): static
    {
        return parent::prefix($label, $isInline);
    }

    public function suffix(string|Htmlable|Closure|null $label, bool|Closure $isInline = true): static
    {
        return parent::suffix($label, $isInline);
    }

    public function prefixIcon(string|BackedEnum|Htmlable|Closure|null $icon, bool|Closure $isInline = true): static
    {
        return parent::prefixIcon($icon, $isInline);
    }

    public function suffixIcon(string|BackedEnum|Htmlable|Closure|null $icon, bool|Closure $isInline = true): static
    {
        return parent::suffixIcon($icon, $isInline);
    }

    public function prefixAction(Action|Closure $action, bool|Closure $isInline = true): static
    {
        return parent::prefixAction($action, $isInline);
    }

    /**
     * @param  array<Action | Closure>  $actions
     */
    public function prefixActions(array $actions, bool|Closure $isInline = true): static
    {
        return parent::prefixActions($actions, $isInline);
    }

    public function suffixAction(Action|Closure $action, bool|Closure $isInline = true): static
    {
        return parent::suffixAction($action, $isInline);
    }

    /**
     * @param  array<Action | Closure>  $actions
     */
    public function suffixActions(array $actions, bool|Closure $isInline = true): static
    {
        return parent::suffixActions($actions, $isInline);
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function speechDictation(bool|Closure $condition = true): static
    {
        $this->speechDictation = $condition;

        return $this;
    }

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

    public function characterCounter(bool|Closure $condition = true): static
    {
        $this->showCharacterCounter = $condition;

        return $this;
    }

    public function clearable(bool|Closure $condition = true): static
    {
        $this->clearable = $condition;

        return $this;
    }

    /**
     * Show a spinner while Livewire processes a request for this field's state path.
     * Pair with {@see live()} (or any update that triggers a round-trip) — the indicator
     * reflects network/sync time, not a built-in validation step on its own.
     */
    public function loading(bool|Closure $condition = true): static
    {
        $this->loadingIndicator = $condition;

        return $this;
    }

    public function validating(bool|Closure $condition = true): static
    {
        return $this->loading($condition);
    }

    public function passwordStrength(bool|Closure $condition = true): static
    {
        $this->passwordStrength = $condition;

        return $this;
    }

    /**
     * Customize labels for password strength scores 0–4.
     *
     * Accepts a list (`['Bardzo słabe', 'Słabe', …]`), score-indexed map (`0 => '…', 3 => '…'`),
     * level keys (`very_weak`, `weak`, `fair`, `good`, `strong`), or default-label keys
     * (`'Very weak' => 'Bardzo słabe'`).
     *
     * @param  array<int, string>|array<string, string>|Closure  $labels
     */
    public function passwordStrengthLabels(array|Closure $labels): static
    {
        $this->passwordStrengthLabels = $labels;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getPasswordStrengthLabels(): array
    {
        $defaults = self::defaultPasswordStrengthLabels();
        $custom = $this->evaluate($this->passwordStrengthLabels);

        if (! is_array($custom)) {
            return $defaults;
        }

        if ($custom === []) {
            return $defaults;
        }

        $resolved = $defaults;

        if (array_is_list($custom)) {
            foreach ($custom as $index => $label) {
                if ($index >= 0 && $index <= 4) {
                    $resolved[$index] = (string) $label;
                }
            }

            return $resolved;
        }

        $namedKeys = [
            'very_weak' => 0,
            'weak' => 1,
            'fair' => 2,
            'good' => 3,
            'strong' => 4,
        ];

        foreach ($custom as $key => $label) {
            if (is_int($key) && $key >= 0 && $key <= 4) {
                $resolved[$key] = (string) $label;

                continue;
            }

            if (! is_string($key)) {
                continue;
            }

            $normalizedKey = strtolower(str_replace(['-', ' '], '_', $key));

            if (isset($namedKeys[$normalizedKey])) {
                $resolved[$namedKeys[$normalizedKey]] = (string) $label;

                continue;
            }

            foreach ($defaults as $index => $defaultLabel) {
                if (strtolower($defaultLabel) === strtolower($key)) {
                    $resolved[$index] = (string) $label;

                    break;
                }
            }
        }

        return $resolved;
    }

    public function copyable(
        bool|Closure $condition = true,
        string|Closure|null $copyMessage = null,
        int|Closure|null $copyMessageDuration = null,
    ): static {
        parent::copyable($condition, $copyMessage, $copyMessageDuration);

        $this->applyOutlineSuffixActionIcons(
            copy: true,
        );

        return $this;
    }

    public function revealable(bool|Closure $condition = true): static
    {
        parent::revealable($condition);

        $this->applyOutlineSuffixActionIcons(
            showPassword: true,
            hidePassword: true,
        );

        return $this;
    }

    /**
     * @param  bool  $copy  Apply outline icon to copy action.
     * @param  bool  $showPassword  Apply outline icon to show password action.
     * @param  bool  $hidePassword  Apply outline icon to hide password action.
     */
    protected function applyOutlineSuffixActionIcons(
        bool $copy = false,
        bool $showPassword = false,
        bool $hidePassword = false,
    ): void {
        $this->cachedSuffixActions = null;

        foreach ($this->getSuffixActions() as $action) {
            match ($action->getName()) {
                CopyAction::getDefaultName() => $copy
                    ? $action->icon($this->getCopyIcon())
                    : null,
                ShowPasswordAction::getDefaultName() => $showPassword
                    ? $action->icon($this->getShowPasswordIcon())
                    : null,
                HidePasswordAction::getDefaultName() => $hidePassword
                    ? $action->icon($this->getHidePasswordIcon())
                    : null,
                default => null,
            };
        }
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat', 'soft'], true)) {
            throw new InvalidArgumentException("Flex text input variant [{$variant}] is not supported.");
        }

        return $variant;
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

    public function copyIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->copyIcon = $icon;

        if ($this->isCopyable()) {
            $this->applyOutlineSuffixActionIcons(copy: true);
        }

        return $this;
    }

    public function showPasswordIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->showPasswordIcon = $icon;

        if ($this->isPasswordRevealable()) {
            $this->applyOutlineSuffixActionIcons(showPassword: true);
        }

        return $this;
    }

    public function hidePasswordIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->hidePasswordIcon = $icon;

        if ($this->isPasswordRevealable()) {
            $this->applyOutlineSuffixActionIcons(hidePassword: true);
        }

        return $this;
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

    public function getCopyIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->copyIcon);

        if (filled($icon)) {
            return $icon;
        }

        $configured = config('filament-flex-fields.ui.flex_text_input_copy_icon');

        return is_string($configured) && filled($configured)
            ? $configured
            : GravityIcon::Copy;
    }

    public function getShowPasswordIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->showPasswordIcon);

        if (filled($icon)) {
            return $icon;
        }

        $configured = config('filament-flex-fields.ui.flex_text_input_show_password_icon');

        return is_string($configured) && filled($configured)
            ? $configured
            : GravityIcon::Eye;
    }

    public function getHidePasswordIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->hidePasswordIcon);

        if (filled($icon)) {
            return $icon;
        }

        $configured = config('filament-flex-fields.ui.flex_text_input_hide_password_icon');

        return is_string($configured) && filled($configured)
            ? $configured
            : GravityIcon::EyeClosed;
    }

    public function getEmojiIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->emojiIcon);

        if (filled($icon)) {
            return $icon;
        }

        $configured = config('filament-flex-fields.ui.flex_text_input_emoji_icon');

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

        $configured = config('filament-flex-fields.ui.flex_text_input_microphone_icon');

        return is_string($configured) && filled($configured)
            ? $configured
            : GravityIcon::Microphone;
    }

    public function shouldShowCharacterCounter(): bool
    {
        return (bool) $this->evaluate($this->showCharacterCounter);
    }

    public function isClearable(): bool
    {
        return (bool) $this->evaluate($this->clearable);
    }

    public function shouldShowLoadingIndicator(): bool
    {
        return (bool) $this->evaluate($this->loadingIndicator);
    }

    public function getLoadingWireTargets(): string
    {
        $targets = array_filter([
            $this->getStatePath(),
            $this->getLivewireKey(),
        ]);

        return implode(',', array_unique($targets));
    }

    public function shouldShowPasswordStrength(): bool
    {
        if (! $this->isPassword()) {
            return false;
        }

        return (bool) $this->evaluate($this->passwordStrength);
    }

    /**
     * @return array{score: int, label: string, percent: float|int}
     */
    public function calculatePasswordStrength(string $password): array
    {
        if ($password === '') {
            return [
                'score' => 0,
                'label' => '',
                'percent' => 0,
            ];
        }

        $score = 0;

        if (strlen($password) >= 8) {
            $score++;
        }

        if (strlen($password) >= 12) {
            $score++;
        }

        if (preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)) {
            $score++;
        }

        if (preg_match('/\d/', $password)) {
            $score++;
        }

        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $score++;
        }

        $score = min(4, $score);

        $labels = $this->getPasswordStrengthLabels();

        return [
            'score' => $score,
            'label' => $labels[$score],
            'percent' => ($score / 4) * 100,
        ];
    }

    public function getCharacterLimit(): ?int
    {
        $max = $this->getMaxLength();

        return $max === null ? null : (int) $max;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-flex-text-input-field',
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-flex-text-input-field--'.$this->getVariant(),
        ];
    }

    public function verificationStatus(string|Htmlable|Closure|null $status): static
    {
        $this->verificationStatus = $status;

        return $this;
    }

    public function getVerificationStatus(): string|Htmlable|null
    {
        return $this->evaluate($this->verificationStatus);
    }

    public function hasVerificationStatus(): bool
    {
        return filled($this->getVerificationStatus());
    }

    public function verificationStatusIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->verificationStatusIcon = $icon;

        return $this;
    }

    public function getVerificationStatusIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->verificationStatusIcon) ?? GravityIcon::SealCheck;
    }

    public function verificationStatusColor(string|Closure $color): static
    {
        $this->verificationStatusColor = $color;

        return $this;
    }

    public function getVerificationStatusColor(): string
    {
        return (string) $this->evaluate($this->verificationStatusColor);
    }
}
