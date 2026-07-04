<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Closure;
use InvalidArgumentException;

trait HasNumericInputOptions
{
    protected int|Closure|null $maxLength = null;

    protected string|Closure $roundingMode = 'truncate';

    public function maxLength(int|Closure|null $length): static
    {
        $this->maxLength = $length;

        $this->rule(static function (self $component): Closure {
            return static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! filled($value)) {
                    return;
                }

                $maxLength = $component->getMaxLength();

                if ($maxLength === null) {
                    return;
                }

                $normalized = preg_replace('/\D/', '', (string) $value) ?? '';

                if (strlen($normalized) > $maxLength) {
                    $fail(__('validation.max_digits', [
                        'attribute' => $attribute,
                        'max' => $maxLength,
                    ]));
                }
            };
        }, static fn (self $component): bool => filled($component->getMaxLength()));

        return $this;
    }

    public function roundingMode(string|Closure $mode): static
    {
        $this->roundingMode = $mode;

        return $this;
    }

    public function getMaxLength(): ?int
    {
        $length = $this->evaluate($this->maxLength);

        return is_int($length) ? $length : null;
    }

    public function getRoundingMode(): string
    {
        $mode = (string) $this->evaluate($this->roundingMode);

        if (! in_array($mode, ['round', 'ceil', 'floor', 'truncate'], true)) {
            throw new InvalidArgumentException("Numeric rounding mode [{$mode}] is not supported.");
        }

        return $mode;
    }
}
