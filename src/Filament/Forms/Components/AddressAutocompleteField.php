<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Concerns\InteractsWithGeocodedAddress;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class AddressAutocompleteField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasPlaceholder;
    use InteractsWithGeocodedAddress;

    /**
     * @var list<string>
     */
    public const array ALL_FIELDS = [
        'street',
        'city',
        'region',
        'postcode',
        'country',
        'country_name',
        'place_name',
    ];

    protected string $view = 'filament-flex-fields::forms.components.address-autocomplete-field';

    protected string|Closure $variant = 'primary';

    protected bool|Closure $searchable = true;

    protected string|BackedEnum|Htmlable|Closure|null $prefixIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $clearIcon = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->geocodedAddressFields = ['street', 'city', 'postcode', 'country', 'country_name', 'place_name'];
        $this->language('pl');

        $this->setUpGeocodedAddress();
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat'], true)) {
            throw new InvalidArgumentException("Address autocomplete variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function searchable(bool|Closure $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->searchable);
    }

    public function prefixIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->prefixIcon = $icon;

        return $this;
    }

    public function clearIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->clearIcon = $icon;

        return $this;
    }

    public function getDefaultPrefixIcon(): string|BackedEnum|Htmlable
    {
        $icon = config('filament-flex-fields.ui.address_autocomplete_prefix_icon');

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return GravityIcon::MapPin;
    }

    public function getPrefixIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->prefixIcon);

        return $icon ?? $this->getDefaultPrefixIcon();
    }

    public function getDefaultClearIcon(): string|BackedEnum|Htmlable
    {
        $icon = config('filament-flex-fields.ui.address_autocomplete_clear_icon');

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return GravityIcon::CircleXmark;
    }

    public function getClearIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->clearIcon);

        return $icon ?? $this->getDefaultClearIcon();
    }

    /**
     * @return list<string>
     */
    protected function geocodedAddressFieldKeys(): array
    {
        return self::ALL_FIELDS;
    }

    protected function geocodedAddressTranslationKey(): string
    {
        return 'address_autocomplete';
    }

    protected function shouldInferPlaceNameFromCoordinates(): bool
    {
        return false;
    }

    /**
     * @return array<string, bool>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-address-autocomplete-field',
            'fff-flex-text-input-field',
            'fff-address-autocomplete-field--'.$this->getSize(),
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-address-autocomplete-field--'.$this->getVariant(),
            'fff-flex-text-input-field--'.$this->getVariant(),
        ];
    }
}
