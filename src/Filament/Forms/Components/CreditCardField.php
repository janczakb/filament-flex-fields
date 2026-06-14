<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Closure;
use Filament\Forms\Components\Field;
use InvalidArgumentException;

class CreditCardField extends Field
{
    use HasControlSize;
    use HasFieldFocusOutline;

    protected string $view = 'filament-flex-fields::forms.components.credit-card-field';

    protected string|Closure $variant = 'midnight';

    protected string|Closure $inputVariant = 'primary';

    protected bool|Closure $flipOnCvvFocus = true;

    protected string|Closure|null $numberLabel = null;

    protected string|Closure|null $nameLabel = null;

    protected string|Closure|null $expiryLabel = null;

    protected string|Closure|null $cvvLabel = null;

    protected string|Closure|null $mark = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (CreditCardField $component, mixed $state): void {
            $component->state($component->normalizeState(is_array($state) ? $state : []));
        });

        $this->dehydrateStateUsing(fn (CreditCardField $component, mixed $state): array => $component->dehydrateStateForStorage(is_array($state) ? $state : []));

        $this->rule(function (CreditCardField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! is_array($value)) {
                    $fail(__('validation.array', ['attribute' => $attribute]));

                    return;
                }

                $normalized = $component->normalizeState($value);

                if ($component->isRequired()) {
                    if ($normalized['number'] === '') {
                        $fail(__('validation.required', ['attribute' => $component->getNumberLabel()]));

                        return;
                    }

                    if ($normalized['name'] === '') {
                        $fail(__('validation.required', ['attribute' => $component->getNameLabel()]));

                        return;
                    }

                    if ($normalized['expiry'] === '') {
                        $fail(__('validation.required', ['attribute' => $component->getExpiryLabel()]));

                        return;
                    }

                    if ($normalized['cvv'] === '') {
                        $fail(__('validation.required', ['attribute' => $component->getCvvLabel()]));

                        return;
                    }
                }

                $digits = $normalized['number'];

                if ($digits !== '' && (strlen($digits) < 13 || strlen($digits) > 19)) {
                    $fail(__('filament-flex-fields::default.validation.credit_card.invalid_number'));

                    return;
                }

                if ($digits !== '' && ! $component->passesLuhnCheck($digits)) {
                    $fail(__('filament-flex-fields::default.validation.credit_card.invalid_number'));

                    return;
                }

                if ($normalized['expiry'] !== '') {
                    $expiryError = $component->getExpiryValidationMessage($normalized['expiry']);

                    if ($expiryError !== null) {
                        $fail($expiryError);

                        return;
                    }
                }

                if ($normalized['cvv'] !== '' && ! preg_match('/^\d{3,4}$/', $normalized['cvv'])) {
                    $fail(__('filament-flex-fields::default.validation.credit_card.invalid_cvv'));
                }
            };
        });
    }

    public function getRequiredValidationRule(): string|Closure
    {
        return 'nullable';
    }

    protected function defaultFocusOutline(): bool
    {
        return true;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function inputVariant(string|Closure $inputVariant): static
    {
        $this->inputVariant = $inputVariant;

        return $this;
    }

    public function flipOnCvvFocus(bool|Closure $condition = true): static
    {
        $this->flipOnCvvFocus = $condition;

        return $this;
    }

    public function numberLabel(string|Closure|null $label): static
    {
        $this->numberLabel = $label;

        return $this;
    }

    public function nameLabel(string|Closure|null $label): static
    {
        $this->nameLabel = $label;

        return $this;
    }

    public function expiryLabel(string|Closure|null $label): static
    {
        $this->expiryLabel = $label;

        return $this;
    }

    public function cvvLabel(string|Closure|null $label): static
    {
        $this->cvvLabel = $label;

        return $this;
    }

    public function mark(string|Closure|null $mark): static
    {
        $this->mark = $mark;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['midnight', 'ocean', 'sunset', 'slate'], true)) {
            throw new InvalidArgumentException("Credit card variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function getInputVariant(): string
    {
        $inputVariant = (string) $this->evaluate($this->inputVariant);

        if (! in_array($inputVariant, ['primary', 'secondary', 'flat'], true)) {
            throw new InvalidArgumentException("Credit card input variant [{$inputVariant}] is not supported.");
        }

        return $inputVariant;
    }

    public function shouldFlipOnCvvFocus(): bool
    {
        return (bool) $this->evaluate($this->flipOnCvvFocus);
    }

    public function getNumberLabel(): string
    {
        $label = $this->evaluate($this->numberLabel);

        return filled($label)
            ? (string) $label
            : __('filament-flex-fields::default.credit_card.number');
    }

    public function getNameLabel(): string
    {
        $label = $this->evaluate($this->nameLabel);

        return filled($label)
            ? (string) $label
            : __('filament-flex-fields::default.credit_card.name');
    }

    public function getExpiryLabel(): string
    {
        $label = $this->evaluate($this->expiryLabel);

        return filled($label)
            ? (string) $label
            : __('filament-flex-fields::default.credit_card.expiry');
    }

    public function getCvvLabel(): string
    {
        $label = $this->evaluate($this->cvvLabel);

        return filled($label)
            ? (string) $label
            : __('filament-flex-fields::default.credit_card.cvv');
    }

    public function getMark(): string
    {
        $mark = $this->evaluate($this->mark);

        return filled($mark)
            ? (string) $mark
            : __('filament-flex-fields::default.credit_card.mark');
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{number: string, name: string, expiry: string}
     */
    public function dehydrateStateForStorage(array $state): array
    {
        $normalized = $this->normalizeState($state);

        return [
            'number' => $normalized['number'],
            'name' => $normalized['name'],
            'expiry' => $normalized['expiry'],
        ];
    }

    public function passesLuhnCheck(string $number): bool
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';

        if ($digits === '') {
            return false;
        }

        $sum = 0;
        $length = strlen($digits);
        $parity = $length % 2;

        for ($index = 0; $index < $length; $index++) {
            $digit = (int) $digits[$index];

            if ($index % 2 === $parity) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{number: string, name: string, expiry: string, cvv: string}
     */
    public function normalizeState(array $state): array
    {
        $number = preg_replace('/\D/', '', (string) ($state['number'] ?? '')) ?? '';
        $name = trim((string) ($state['name'] ?? ''));
        $expiry = $this->normalizeExpiry((string) ($state['expiry'] ?? ''));
        $cvv = preg_replace('/\D/', '', (string) ($state['cvv'] ?? '')) ?? '';

        if (strlen($cvv) > 4) {
            $cvv = substr($cvv, 0, 4);
        }

        if (strlen($number) > 19) {
            $number = substr($number, 0, 19);
        }

        return [
            'number' => $number,
            'name' => $name,
            'expiry' => $expiry,
            'cvv' => $cvv,
        ];
    }

    protected function normalizeExpiry(string $expiry): string
    {
        $digits = preg_replace('/\D/', '', $expiry) ?? '';

        if ($digits === '') {
            return '';
        }

        $digits = substr($digits, 0, 4);

        if (strlen($digits) <= 2) {
            return $digits;
        }

        return substr($digits, 0, 2).'/'.substr($digits, 2);
    }

    public function getExpiryValidationMessage(string $expiry): ?string
    {
        if ($expiry === '') {
            return null;
        }

        if (! preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $matches)) {
            return __('filament-flex-fields::default.validation.credit_card.invalid_expiry');
        }

        $month = (int) $matches[1];
        $year = 2000 + (int) $matches[2];
        $expiryYearMonth = ($year * 100) + $month;

        if ($expiryYearMonth < (int) now()->format('Ym')) {
            return __('filament-flex-fields::default.validation.credit_card.expired');
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-credit-card-field',
            'fff-credit-card-field--'.$this->getSize(),
            'fff-credit-card-field--'.$this->getVariant(),
            'fff-credit-card-field--input-'.$this->getInputVariant(),
        ];
    }
}
