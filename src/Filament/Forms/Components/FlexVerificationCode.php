<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FlexVerificationCode extends Field
{
    use HasControlSize;

    protected string $view = 'filament-flex-fields::forms.components.flex-verification-code';

    protected int|Closure $length = 6;

    /**
     * @var array<int, int>|Closure|null
     */
    protected array|Closure|null $groups = null;

    protected string|Closure|null $groupSeparator = null;

    protected string|Closure $allowedCharacters = 'numeric';

    protected string|Closure|null $color = 'primary';

    protected bool|Closure $autoSubmit = false;

    protected string|Closure|null $autoSubmitMethod = null;

    protected ?Closure $autoSubmitUsing = null;

    protected bool|Closure $loadingIndicator = false;

    protected string|Htmlable|Closure|null $heading = null;

    protected string|Htmlable|Closure|null $description = null;

    protected string|Htmlable|Closure|null $footer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default('');

        $this->afterStateHydrated(function (FlexVerificationCode $component, mixed $state): void {
            $component->state($component->normalizeState(is_string($state) ? $state : ''));
        });

        $this->dehydrateStateUsing(fn (FlexVerificationCode $component, mixed $state): string => $component->normalizeState(is_string($state) ? $state : ''));

        $this->afterStateUpdated(function (FlexVerificationCode $component, mixed $state): void {
            if (! $component->shouldAutoSubmitUsingServerCallback()) {
                return;
            }

            $normalized = $component->normalizeState(is_string($state) ? $state : '');

            if (strlen($normalized) !== $component->getLength()) {
                return;
            }

            $component->evaluate($component->autoSubmitUsing, [
                'state' => $normalized,
                'code' => $normalized,
            ]);
        });

        $this->rule(function (FlexVerificationCode $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                $raw = is_string($value) ? $value : '';

                if ($component->isRequired() && $raw === '') {
                    $fail(__('validation.required', ['attribute' => $attribute]));

                    return;
                }

                if ($raw === '') {
                    return;
                }

                if (! preg_match($component->getInputValidationPattern(), $raw)) {
                    $fail(__('filament-flex-fields::default.validation.verification_code.invalid_characters'));

                    return;
                }

                $normalized = $component->normalizeState($raw);

                if (strlen($normalized) !== $component->getLength()) {
                    $fail(__('filament-flex-fields::default.validation.verification_code.incomplete', [
                        'length' => $component->getLength(),
                    ]));
                }
            };
        });
    }

    public function length(int|Closure $length): static
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @param  array<int, int>|Closure|null  $groups
     */
    public function groups(array|Closure|null $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * @param  array<int, int>|Closure|null  $groupSizes
     */
    public function groupSizes(array|Closure|null $groupSizes): static
    {
        return $this->groups($groupSizes);
    }

    public function groupSeparator(string|Closure|null $separator): static
    {
        $this->groupSeparator = $separator;

        return $this;
    }

    public function allowedCharacters(string|Closure $allowedCharacters): static
    {
        $this->allowedCharacters = $allowedCharacters;

        return $this;
    }

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Automatically submit when all digits are filled.
     *
     * Pair with {@see autoSubmitMethod()} for a Livewire action or {@see submitUsing()} for a PHP callback.
     */
    public function autoSubmit(bool|Closure $condition = true): static
    {
        $this->autoSubmit = $condition;

        return $this;
    }

    /**
     * Call a Livewire method when the code is complete.
     *
     * The method receives the normalized code string as its first argument.
     */
    public function autoSubmitMethod(string|Closure|null $method): static
    {
        $this->autoSubmitMethod = $method;

        if ($method !== null) {
            $this->autoSubmit(true);
        }

        return $this;
    }

    /**
     * Run a PHP callback when the code is complete.
     *
     * Enables {@see live()} with debounce automatically when the field is not already live.
     * Inject `$state` / `$code`, `$livewire`, `$set`, `$get`, and other Filament utilities into the callback.
     */
    public function submitUsing(Closure $callback): static
    {
        $this->autoSubmitUsing = $callback;
        $this->autoSubmit(true);

        if ($this->evaluate($this->isLive) !== true) {
            $this->live(debounce: 250);
        }

        return $this;
    }

    /**
     * Show a spinner while Livewire processes a request for this field or auto-submit action.
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

    public function heading(string|Htmlable|Closure|null $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->evaluate($this->heading);
    }

    public function description(string|Htmlable|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string|Htmlable|null
    {
        return $this->evaluate($this->description);
    }

    public function footer(string|Htmlable|Closure|null $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    public function getFooter(): string|Htmlable|null
    {
        return $this->evaluate($this->footer);
    }

    /**
     * Register a link-style footer action (e.g. Resend).
     *
     * Pass a Closure to create a default link action with the translated Resend label.
     */
    public function footerAction(Action|Closure|null $action): static
    {
        if ($action instanceof Closure) {
            $action = Action::make($this->getDefaultFooterActionName())
                ->label(__('filament-flex-fields::default.verification_code.resend'))
                ->link()
                ->action($action);
        }

        if ($action instanceof Action && ! $action->isLink()) {
            $action->link();
        }

        $this->action($action);

        if ($action !== null) {
            $this->assignFooterActionKey($action);
        }

        return $this;
    }

    public function getFooterAction(): ?Action
    {
        return $this->getAction();
    }

    public function hasHeaderContent(): bool
    {
        return filled($this->getHeading()) || filled($this->getDescription());
    }

    public function hasFooterContent(): bool
    {
        return filled($this->getFooter()) || $this->hasFooterAction();
    }

    public function hasFooterAction(): bool
    {
        return $this->getFooterAction() !== null;
    }

    public function hasLayoutChrome(): bool
    {
        return $this->hasHeaderContent() || $this->hasFooterContent();
    }

    protected function getDefaultFooterActionName(): string
    {
        return Str::slug($this->getName()).'-footer-action';
    }

    protected function assignFooterActionKey(Action $action): void
    {
        if ($this->hasStatePath() || filled($this->evaluate($this->key))) {
            return;
        }

        $this->key(Str::kebab($action->getName()));
    }

    public function getLength(): int
    {
        $length = (int) $this->evaluate($this->length);

        if ($length < 1) {
            throw new InvalidArgumentException('Verification code length must be at least 1.');
        }

        if ($length > 16) {
            throw new InvalidArgumentException('Verification code length cannot exceed 16.');
        }

        return $length;
    }

    /**
     * @return list<int>
     */
    public function getResolvedGroups(): array
    {
        $groups = $this->evaluate($this->groups);
        $length = $this->getLength();

        if ($groups === null || $groups === []) {
            return [$length];
        }

        if (! is_array($groups)) {
            throw new InvalidArgumentException('Verification code groups must be an array of positive integers.');
        }

        $normalized = array_map(
            fn (mixed $size): int => (int) $size,
            $groups,
        );

        foreach ($normalized as $size) {
            if ($size < 1) {
                throw new InvalidArgumentException('Each verification code group must contain at least one field.');
            }
        }

        if (array_sum($normalized) !== $length) {
            throw new InvalidArgumentException("Verification code groups must sum to {$length}.");
        }

        return $normalized;
    }

    public function getGroupSeparator(): ?string
    {
        $separator = $this->evaluate($this->groupSeparator);

        return filled($separator) ? (string) $separator : null;
    }

    public function shouldShowSeparators(): bool
    {
        return count($this->getResolvedGroups()) > 1;
    }

    public function getAllowedCharacters(): string
    {
        $allowedCharacters = (string) $this->evaluate($this->allowedCharacters);

        if (! in_array($allowedCharacters, ['numeric', 'alphanumeric'], true)) {
            throw new InvalidArgumentException("Allowed characters [{$allowedCharacters}] are not supported.");
        }

        return $allowedCharacters;
    }

    public function isNumeric(): bool
    {
        return $this->getAllowedCharacters() === 'numeric';
    }

    public function getColor(): ?string
    {
        return $this->evaluate($this->color);
    }

    public function shouldAutoSubmit(): bool
    {
        return (bool) $this->evaluate($this->autoSubmit);
    }

    public function getAutoSubmitMethod(): ?string
    {
        if (! $this->shouldAutoSubmit()) {
            return null;
        }

        $method = $this->evaluate($this->autoSubmitMethod);

        return filled($method) ? (string) $method : null;
    }

    public function shouldAutoSubmitUsingServerCallback(): bool
    {
        return $this->shouldAutoSubmit() && $this->autoSubmitUsing instanceof Closure;
    }

    public function shouldShowLoadingIndicator(): bool
    {
        return (bool) $this->evaluate($this->loadingIndicator);
    }

    public function getLoadingWireTargets(): string
    {
        $targets = [$this->getStatePath()];

        if ($method = $this->getAutoSubmitMethod()) {
            $targets[] = $method;
        }

        return implode(',', array_unique($targets));
    }

    public function getInputMode(): string
    {
        return $this->isNumeric() ? 'numeric' : 'text';
    }

    public function getValidationPattern(): string
    {
        $length = $this->getLength();

        return match ($this->getAllowedCharacters()) {
            'numeric' => '/^\d{'.$length.'}$/',
            'alphanumeric' => '/^[A-Za-z0-9]{'.$length.'}$/',
        };
    }

    public function getInputValidationPattern(): string
    {
        return match ($this->getAllowedCharacters()) {
            'numeric' => '/^\d*$/',
            'alphanumeric' => '/^[A-Za-z0-9]*$/',
        };
    }

    public function getAllowedCharacterPattern(): string
    {
        return match ($this->getAllowedCharacters()) {
            'numeric' => '/[0-9]/',
            'alphanumeric' => '/[A-Za-z0-9]/',
        };
    }

    public function normalizeState(string $state): string
    {
        $length = $this->getLength();
        $pattern = $this->getAllowedCharacterPattern();
        $characters = [];

        foreach (mb_str_split($state) as $character) {
            if (! preg_match($pattern, $character)) {
                continue;
            }

            $characters[] = $this->isNumeric()
                ? $character
                : strtoupper($character);

            if (count($characters) >= $length) {
                break;
            }
        }

        return implode('', $characters);
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-verification-code',
            'fff-verification-code--'.$this->getSize(),
        ];

        if ($color = $this->getColor()) {
            $classes[] = 'fi-color-'.$color;
        }

        return $classes;
    }

    public function getDigitAriaLabel(int $index): string
    {
        return __('filament-flex-fields::default.verification_code.digit', [
            'current' => $index + 1,
            'total' => $this->getLength(),
        ]);
    }
}
