<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Enums\GeocodingSearchScope;
use Bjanczak\FilamentFlexFields\Enums\MapboxSearchType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SocialLinksField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\SocialLinksFieldConfigurator;

it('maps GeocodingSearchScope presets to Mapbox types', function (): void {
    expect(GeocodingSearchScope::Address->searchTypes())->toBe([MapboxSearchType::Address->value])
        ->and(GeocodingSearchScope::Address->streetAddressesOnly())->toBeTrue()
        ->and(GeocodingSearchScope::Poi->searchTypes())->toBe([MapboxSearchType::Poi->value])
        ->and(GeocodingSearchScope::Cities->searchTypes())->toBe([
            MapboxSearchType::Place->value,
            MapboxSearchType::Locality->value,
            MapboxSearchType::Neighborhood->value,
        ])
        ->and(GeocodingSearchScope::Regions->searchTypes())->toBe([
            MapboxSearchType::Region->value,
            MapboxSearchType::District->value,
        ])
        ->and(GeocodingSearchScope::All->searchTypes())->toBeNull()
        ->and(GeocodingSearchScope::Custom->searchTypes())->toBeNull();
});

it('builds social links fields from FieldType config', function (): void {
    $field = (new FlexFieldFormBuilder)->makeComponent(new FlexFieldDefinition(
        slug: 'socials',
        label: 'Socials',
        type: FieldType::SocialLinks,
        config: [
            'platforms' => ['instagram', 'facebook'],
            'max_links' => 2,
            'reorderable' => true,
        ],
    ));

    $platforms = (new ReflectionProperty(SocialLinksField::class, 'platforms'))->getValue($field);

    expect($field)->toBeInstanceOf(SocialLinksField::class)
        ->and($platforms)->toBe(['instagram', 'facebook'])
        ->and($field->getMaxLinks())->toBe(2)
        ->and($field->isReorderable())->toBeTrue();
});

it('applies social links whitelist through the configurator', function (): void {
    $field = SocialLinksField::make('socials');

    (new SocialLinksFieldConfigurator)->configureSocialLinksField($field, [
        'platforms' => ['instagram', 'facebook'],
        'max_links' => 3,
        'reorderable' => true,
        'auto_format_urls' => false,
        'size' => 'sm',
        'variant' => 'soft',
    ]);

    $platforms = (new ReflectionProperty(SocialLinksField::class, 'platforms'))->getValue($field);

    expect($platforms)->toBe(['instagram', 'facebook'])
        ->and($field->getMaxLinks())->toBe(3)
        ->and($field->isReorderable())->toBeTrue()
        ->and($field->shouldAutoFormatUrls())->toBeFalse()
        ->and($field->getSize())->toBe('sm')
        ->and($field->getVariant())->toBe('soft');
});
