<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexFieldsPlaygroundStore;
use Bjanczak\FilamentFlexFields\Tests\Support\SelectPayloadPost;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
| Manual Select stress — excluded from default Pest / CI.
| Run: FFF_SELECT_ATOMICS_FULL=1 composer test:select-atomics
*/

beforeEach(function (): void {
    if (! filter_var(getenv('FFF_SELECT_ATOMICS_FULL') ?: '', FILTER_VALIDATE_BOOLEAN)) {
        $this->markTestSkipped(
            'Select cascade atomics are manual-only. Run: FFF_SELECT_ATOMICS_FULL=1 composer test:select-atomics'
        );
    }

    Schema::dropIfExists('select_payload_posts');

    Schema::create('select_payload_posts', function (Blueprint $table): void {
        $table->id();
        $table->json('payload')->nullable();
        $table->timestamps();
    });
});

/**
 * @return list<array{0: string, 1: string|null, 2: list<string>, 3: string|null}>
 */
function cascadeCountryRegionCases(): array
{
    $map = [
        'us' => ['ca', 'tx', 'ny'],
        'pl' => ['mz', 'wp', 'pm'],
        'ae' => ['du', 'az', 'sh'],
    ];

    $rows = [];

    foreach ($map as $country => $regions) {
        $rows["empty_until_{$country}"] = [$country, null, $regions, null];

        foreach ($regions as $region) {
            $rows["{$country}_{$region}"] = [$country, $region, $regions, null];
            $rows["{$country}_{$region}_clear"] = [$country, $region, $regions, null];
        }

        foreach ($regions as $region) {
            foreach (array_keys($map) as $nextCountry) {
                if ($nextCountry === $country) {
                    continue;
                }

                $rows["switch_{$country}_{$region}_to_{$nextCountry}"] = [$country, $region, $regions, $nextCountry];
            }
        }
    }

    for ($i = 0; $i < 40; $i++) {
        $countries = array_keys($map);
        $country = $countries[$i % count($countries)];
        $region = $map[$country][$i % count($map[$country])];
        $rows["hammer_{$i}"] = [$country, $region, $map[$country], null];
    }

    return $rows;
}

function cascadeSchema(): array
{
    return [
        Grid::make(2)->schema([
            SelectField::make('select__cascade_country')
                ->options([
                    'us' => 'United States',
                    'pl' => 'Poland',
                    'ae' => 'United Arab Emirates',
                ])
                ->live()
                ->skipRenderAfterStateUpdated()
                ->afterStateUpdated(fn (Set $set) => $set('select__cascade_region', null))
                ->searchable()
                ->clearable(),
            SelectField::make('select__cascade_region')
                ->dependsOn('select__cascade_country', fn (?string $country): array => match ($country) {
                    'us' => [
                        'ca' => 'California',
                        'tx' => 'Texas',
                        'ny' => 'New York',
                    ],
                    'pl' => [
                        'mz' => 'Mazowieckie',
                        'wp' => 'Wielkopolskie',
                        'pm' => 'Pomorskie',
                    ],
                    'ae' => [
                        'du' => 'Dubai',
                        'az' => 'Abu Dhabi',
                        'sh' => 'Sharjah',
                    ],
                    default => [],
                })
                ->searchable()
                ->clearable()
                ->placeholder('Pick a country first'),
        ]),
    ];
}

it('cascade dependsOn + live parent: region options and clear empty', function (string $country, ?string $region, array $allowedRegions, ?string $nextCountry): void {
    TestableTranslatableForm::$formSchema = cascadeSchema();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->set('data.select__cascade_country', $country)
        ->assertSet('data.select__cascade_country', $country);

    $regionField = $livewire->instance()->getSchema('form')->getComponentByStatePath('select__cascade_region');
    $options = collect($livewire->instance()->callSchemaComponentMethod($regionField->getKey(), 'getOptionsForJs'))
        ->pluck('value')
        ->all();

    expect($options)->toEqualCanonicalizing($allowedRegions);

    if ($region !== null) {
        $livewire->set('data.select__cascade_region', $region)
            ->assertSet('data.select__cascade_region', $region);

        // clear × on region → empty persists
        $livewire->set('data.select__cascade_region', null)
            ->assertSet('data.select__cascade_region', null);

        $post = SelectPayloadPost::query()->create([
            'payload' => [
                'select__cascade_country' => $country,
                'select__cascade_region' => null,
            ],
        ]);

        expect($post->fresh()->payload['select__cascade_region'])->toBeNull();

        // refill then clear country (parent ×)
        $livewire->set('data.select__cascade_region', $region)
            ->set('data.select__cascade_country', null)
            ->assertSet('data.select__cascade_country', null);

        // afterStateUpdated clears region when country changes; null country also clears via our set sequence
        $livewire->set('data.select__cascade_region', null)
            ->assertSet('data.select__cascade_region', null);
    }

    if ($nextCountry !== null && $region !== null) {
        $livewire->set('data.select__cascade_country', $country)
            ->set('data.select__cascade_region', $region)
            ->set('data.select__cascade_country', $nextCountry)
            ->assertSet('data.select__cascade_country', $nextCountry)
            ->assertSet('data.select__cascade_region', null);

        $nextField = $livewire->instance()->getSchema('form')->getComponentByStatePath('select__cascade_region');
        $nextOptions = collect($livewire->instance()->callSchemaComponentMethod($nextField->getKey(), 'getOptionsForJs'))
            ->pluck('value')
            ->all();

        expect($nextOptions)->not->toContain($region);
    }
})->with(cascadeCountryRegionCases());

it('cascade empty region until country chosen is expected', function (): void {
    TestableTranslatableForm::$formSchema = cascadeSchema();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->assertSet('data.select__cascade_region', null);

    $regionField = $livewire->instance()->getSchema('form')->getComponentByStatePath('select__cascade_region');
    $options = $livewire->instance()->callSchemaComponentMethod($regionField->getKey(), 'getOptionsForJs');

    expect($options)->toBeArray()->toBeEmpty();
});

it('cascade clear empty persists via playground store', function (): void {
    Cache::flush();
    auth()->login(new GenericUser(['id' => 777]));

    $store = new FlexFieldsPlaygroundStore;
    $store->put('select-field', [
        'select__cascade_country' => 'us',
        'select__cascade_region' => 'ca',
    ]);
    $store->put('select-field', [
        'select__cascade_country' => 'us',
        'select__cascade_region' => null,
    ]);

    expect($store->get('select-field')['select__cascade_region'])->toBeNull();
});

it('dependsOn parent change clears region and drops stale option keys', function (): void {
    TestableTranslatableForm::$formSchema = cascadeSchema();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->set('data.select__cascade_country', 'us')
        ->set('data.select__cascade_region', 'ca')
        ->assertSet('data.select__cascade_region', 'ca');

    // Rapid parent switches (mid-open / mid-fetch analogue): each change clears region
    // via afterStateUpdated and options must track only the latest parent.
    $livewire
        ->set('data.select__cascade_country', 'pl')
        ->assertSet('data.select__cascade_country', 'pl')
        ->assertSet('data.select__cascade_region', null);

    $regionField = $livewire->instance()->getSchema('form')->getComponentByStatePath('select__cascade_region');
    $plOptions = collect($livewire->instance()->callSchemaComponentMethod($regionField->getKey(), 'getOptionsForJs'))
        ->pluck('value')
        ->all();

    expect($plOptions)->toEqualCanonicalizing(['mz', 'wp', 'pm'])
        ->and($plOptions)->not->toContain('ca')
        ->and($plOptions)->not->toContain('tx')
        ->and($plOptions)->not->toContain('ny');

    $livewire
        ->set('data.select__cascade_country', 'ae')
        ->assertSet('data.select__cascade_country', 'ae')
        ->assertSet('data.select__cascade_region', null);

    $aeField = $livewire->instance()->getSchema('form')->getComponentByStatePath('select__cascade_region');
    $aeOptions = collect($livewire->instance()->callSchemaComponentMethod($aeField->getKey(), 'getOptionsForJs'))
        ->pluck('value')
        ->all();

    expect($aeOptions)->toEqualCanonicalizing(['du', 'az', 'sh'])
        ->and($aeOptions)->not->toContain('mz')
        ->and($aeOptions)->not->toContain('ca');
});

it('cascade remount does not resurrect a cleared region from a prior parent', function (): void {
    TestableTranslatableForm::$formSchema = cascadeSchema();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->set('data.select__cascade_country', 'us')
        ->set('data.select__cascade_region', 'tx')
        ->set('data.select__cascade_country', 'pl')
        ->assertSet('data.select__cascade_region', null);

    // Remount the Livewire component with the post-clear state (destroy mid-flow analogue).
    $remounted = Livewire::test(TestableTranslatableForm::class, [
        'data' => [
            'select__cascade_country' => 'pl',
            'select__cascade_region' => null,
        ],
    ])
        ->assertSet('data.select__cascade_country', 'pl')
        ->assertSet('data.select__cascade_region', null);

    $regionField = $remounted->instance()->getSchema('form')->getComponentByStatePath('select__cascade_region');
    $options = collect($remounted->instance()->callSchemaComponentMethod($regionField->getKey(), 'getOptionsForJs'))
        ->pluck('value')
        ->all();

    expect($options)->toEqualCanonicalizing(['mz', 'wp', 'pm'])
        ->and($options)->not->toContain('tx');
});
