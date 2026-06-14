<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Timezones;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class TimezoneField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasPlaceholder;

    protected string $view = 'filament-flex-fields::forms.components.timezone-field';

    protected string|Closure $variant = 'primary';

    protected string|Closure|null $defaultTimezone = null;

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $timezones = null;

    /**
     * @var list<string>|Closure
     */
    protected array|Closure $exceptTimezones = [];

    protected bool|Closure $searchable = true;

    protected bool|Closure $showOffset = true;

    protected bool|Closure $browserTimezoneDefault = false;

    protected bool|Closure $browserTimezoneSortFirst = false;

    protected string|BackedEnum|Htmlable|Closure|null $prefixIcon = null;

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat', 'soft'], true)) {
            throw new InvalidArgumentException("Timezone field variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (TimezoneField $component, mixed $state): void {
            $normalized = $component->normalizeState($state);

            if ($component->shouldUseBrowserTimezoneDefault() && blank($normalized)) {
                $detected = $component->getBrowserTimezoneIdentifier();

                if ($detected !== null) {
                    $normalized = $detected;
                }
            }

            $component->state($normalized);
        });

        $this->dehydrateStateUsing(fn (TimezoneField $component, mixed $state): ?string => $component->normalizeState($state));

        $this->rule(function (TimezoneField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if ($component->isRequired() && blank($value)) {
                    $fail(__('validation.required', ['attribute' => $component->getLabel()]));

                    return;
                }

                if (blank($value)) {
                    return;
                }

                $submitted = trim((string) $value);
                $allowed = $component->getResolvedTimezoneIdentifiers();

                if (! in_array($submitted, $allowed, true)) {
                    $fail(__('validation.in', ['attribute' => $component->getLabel()]));
                }
            };
        });
    }

    public function getRequiredValidationRule(): string|Closure
    {
        return 'nullable';
    }

    /**
     * @param  list<string>|Closure|null  $timezones
     */
    public function timezones(array|Closure|null $timezones): static
    {
        $this->timezones = $timezones;

        return $this;
    }

    /**
     * @param  list<string>|Closure  $timezones
     */
    public function exceptTimezones(array|Closure $timezones): static
    {
        $this->exceptTimezones = $timezones;

        return $this;
    }

    public function defaultTimezone(string|Closure|null $timezone): static
    {
        $this->defaultTimezone = $timezone;

        return $this;
    }

    public function browserTimezoneDefault(bool|Closure $condition = true): static
    {
        $this->browserTimezoneDefault = $condition;

        return $this;
    }

    public function browserTimezoneSortFirst(bool|Closure $condition = true): static
    {
        $this->browserTimezoneSortFirst = $condition;

        return $this;
    }

    public function showOffset(bool|Closure $condition = true): static
    {
        $this->showOffset = $condition;

        return $this;
    }

    public function prefixIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->prefixIcon = $icon;

        return $this;
    }

    public function searchable(bool|Closure $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    public function shouldUseBrowserTimezoneDefault(): bool
    {
        return (bool) $this->evaluate($this->browserTimezoneDefault);
    }

    public function shouldSortTimezonesByBrowserTimezone(): bool
    {
        return (bool) $this->evaluate($this->browserTimezoneSortFirst);
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->searchable);
    }

    public function shouldShowOffset(): bool
    {
        return (bool) $this->evaluate($this->showOffset);
    }

    public function getPrefixIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->prefixIcon);

        return $icon ?? GravityIcon::Clock;
    }

    /**
     * @return list<string>|null
     */
    public function getAllowedTimezoneIdentifiers(): ?array
    {
        $timezones = $this->evaluate($this->timezones);

        if ($timezones === null) {
            return null;
        }

        return array_values($timezones);
    }

    /**
     * @return list<string>
     */
    public function getExceptTimezoneIdentifiers(): array
    {
        return array_values($this->evaluate($this->exceptTimezones));
    }

    /**
     * @return list<string>
     */
    public function getResolvedTimezoneIdentifiers(): array
    {
        return Timezones::resolve(
            $this->getAllowedTimezoneIdentifiers(),
            $this->getExceptTimezoneIdentifiers(),
        );
    }

    public function getBrowserTimezoneIdentifier(): ?string
    {
        return Timezones::fromBrowserTimezone($this->getResolvedTimezoneIdentifiers());
    }

    public function getDefaultTimezoneIdentifier(): ?string
    {
        if ($this->shouldUseBrowserTimezoneDefault()) {
            $detected = $this->getBrowserTimezoneIdentifier();

            if ($detected !== null) {
                return $detected;
            }
        }

        $default = $this->evaluate($this->defaultTimezone);

        if (blank($default)) {
            return null;
        }

        $timezone = (string) $default;
        $allowed = $this->getResolvedTimezoneIdentifiers();

        if (in_array($timezone, $allowed, true)) {
            return $timezone;
        }

        return $allowed[0] ?? null;
    }

    /**
     * @return list<array{id: string, label: string, offset: string, offset_seconds: int, region: string}>
     */
    public function getTimezonesMetadata(): array
    {
        $metadata = Timezones::metadata(
            $this->getAllowedTimezoneIdentifiers(),
            $this->getExceptTimezoneIdentifiers(),
        );

        if ($this->shouldSortTimezonesByBrowserTimezone()) {
            return Timezones::sortWithPreferredFirst(
                $metadata,
                $this->getBrowserTimezoneIdentifier(),
            );
        }

        return $metadata;
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function getTimezoneSelectOptions(): array
    {
        return Timezones::selectOptions(
            $this->getAllowedTimezoneIdentifiers(),
            $this->getExceptTimezoneIdentifiers(),
        );
    }

    public function normalizeState(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        $timezone = trim((string) $state);
        $allowed = $this->getResolvedTimezoneIdentifiers();

        if (in_array($timezone, $allowed, true)) {
            return $timezone;
        }

        $default = $this->getDefaultTimezoneIdentifier();

        if ($default !== null && in_array($default, $allowed, true)) {
            return $default;
        }

        return null;
    }

    /**
     * @return list<array{id: string, label: string, offset: string}>
     */
    public function getOptionsForJs(): array
    {
        return collect($this->getTimezonesMetadata())
            ->map(fn (array $timezone): array => [
                'id' => $timezone['id'],
                'label' => $timezone['label'],
                'offset' => $timezone['offset'],
            ])
            ->values()
            ->all();
    }

    public function getVirtualScrollThreshold(): int
    {
        return 50;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-timezone-field',
            'fff-flex-text-input-field',
            'fff-timezone-field--'.$this->getSize(),
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-timezone-field--'.$this->getVariant(),
            'fff-flex-text-input-field--'.$this->getVariant(),
        ];
    }
}
