<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\InteractsWithGeocodedAddress;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Field;

class MapPickerField extends Field
{
    use CanBeReadOnly;
    use InteractsWithGeocodedAddress;

    /**
     * @var list<string>
     */
    public const array ALL_FIELDS = [
        'lat',
        'lng',
        'street',
        'city',
        'region',
        'postcode',
        'country',
        'country_name',
        'place_name',
    ];

    protected string $view = 'filament-flex-fields::forms.components.map-picker-field';

    /**
     * @var array{0: float, 1: float}|Closure
     */
    protected array|Closure $defaultCenter = [52.2297, 21.0122];

    protected int|Closure $defaultZoom = 12;

    protected bool|Closure $searchable = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->geocodedAddressFields = ['lat', 'lng', 'street', 'city', 'postcode', 'country', 'place_name'];

        $this->setUpGeocodedAddress();
    }

    /**
     * @param  array{0: float, 1: float}|Closure  $center
     */
    public function defaultCenter(array|Closure $center): static
    {
        $this->defaultCenter = $center;

        return $this;
    }

    public function defaultZoom(int|Closure $zoom): static
    {
        $this->defaultZoom = $zoom;

        return $this;
    }

    public function searchable(bool|Closure $condition = true): static
    {
        $this->searchable = $condition;

        return $this;
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function getDefaultCenter(): array
    {
        $center = $this->evaluate($this->defaultCenter);

        if (! is_array($center) || count($center) < 2) {
            return [52.2297, 21.0122];
        }

        return [(float) $center[0], (float) $center[1]];
    }

    public function getDefaultZoom(): int
    {
        return max(1, min(22, (int) $this->evaluate($this->defaultZoom)));
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->searchable);
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
        return 'map_picker';
    }

    protected function shouldInferPlaceNameFromCoordinates(): bool
    {
        return true;
    }

    /**
     * @return array<string, bool>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-map-picker-field' => true,
        ];
    }
}
