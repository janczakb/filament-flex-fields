<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexFieldsPlaygroundStore;
use Bjanczak\FilamentFlexFields\Support\Playground\SelectPlayground;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Filament\Schemas\Components\Grid;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

/**
 * @return array<string, mixed>
 */
function selectPlaygroundDefaults(): array
{
    return app(SelectPlayground::class)->defaultState();
}

/**
 * @return list<string>
 */
function selectPlaygroundKeys(): array
{
    return array_keys(selectPlaygroundDefaults());
}

function selectPlaygroundAlternate(string $key, mixed $current): mixed
{
    if (is_array($current)) {
        if ($current === []) {
            return ['action'];
        }

        $reversed = array_values(array_reverse($current));

        return $reversed === $current ? array_values(array_unique([...$current, 'thriller'])) : $reversed;
    }

    return match ($key) {
        'select__boolean' => true,
        'select__async_paginated', 'select__scale_10k', 'select__required', 'select__create_single' => 'published',
        'select__cascade_region' => 'ca',
        'select__cascade_country' => 'pl',
        'select__rtl', 'select__rtl_inline_field_label', 'select__rtl_dropdown_clearable' => 'jeddah',
        'select__rtl_hebrew_inline' => 'tel_aviv',
        'select__create_with_sections' => 'laravel',
        default => is_string($current) && $current !== 'draft' ? 'draft' : 'published',
    };
}

/**
 * Tiny mirror of playground key cardinality for fast wire round-trips.
 */
function selectPlaygroundAtomicField(string $key, mixed $sample): SelectField
{
    $field = SelectField::make($key)
        ->options([
            'draft' => 'Draft',
            'published' => 'Published',
            'reviewing' => 'Reviewing',
            'action' => 'Action',
            'adventure' => 'Adventure',
            'drama' => 'Drama',
            'comedy' => 'Comedy',
            'horror' => 'Horror',
            'thriller' => 'Thriller',
            'tailwind' => 'Tailwind',
            'laravel' => 'Laravel',
            'livewire' => 'Livewire',
            'alpine' => 'Alpine',
            'jane' => 'Jane',
            'john' => 'John',
            'fred' => 'Fred',
            'pro' => 'Pro',
            'sky' => 'Sky',
            'usa' => 'USA',
            'acme' => 'Acme',
            'dog' => 'Dog',
            'enterprise_agreement' => 'Enterprise',
            'us' => 'US',
            'pl' => 'PL',
            'ca' => 'CA',
            'tx' => 'TX',
            'mz' => 'MZ',
            'riyadh' => 'Riyadh',
            'jeddah' => 'Jeddah',
            'tel_aviv' => 'Tel Aviv',
            'california' => 'California',
            'texas' => 'Texas',
            'delaware' => 'Delaware',
        ])
        ->live()
        ->searchable();

    if (is_array($sample)) {
        $field->multiple();
    }

    if ($key === 'select__boolean') {
        $field->boolean(placeholder: 'Make your mind up...');
    }

    return $field;
}

it('mounts the full select playground and hydrates every defaultState key', function (): void {
    $playground = app(SelectPlayground::class);
    $defaults = $playground->defaultState();

    TestableTranslatableForm::$formSchema = $playground->components();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->call('mountWithPlaygroundState', $defaults);

    foreach ($defaults as $key => $value) {
        expect($livewire->get('data.'.$key))->toEqual($value);
    }

    expect(count($defaults))->toBeGreaterThan(50);
});

it('atomically round-trips every select playground key through Livewire', function (string $key): void {
    $defaults = selectPlaygroundDefaults();
    $current = $defaults[$key];
    $alternate = selectPlaygroundAlternate($key, $current);

    TestableTranslatableForm::$formSchema = [selectPlaygroundAtomicField($key, $current)];

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->set('data.'.$key, $current)
        ->assertSet('data.'.$key, $current)
        ->set('data.'.$key, $alternate)
        ->assertSet('data.'.$key, $alternate)
        ->set('data.'.$key, $current)
        ->assertSet('data.'.$key, $current);

    if (is_array($current)) {
        $livewire->set('data.'.$key, [])
            ->assertSet('data.'.$key, [])
            ->set('data.'.$key, $current)
            ->assertSet('data.'.$key, $current);
    } else {
        $livewire->set('data.'.$key, null)
            ->assertSet('data.'.$key, null)
            ->set('data.'.$key, $current)
            ->assertSet('data.'.$key, $current);
    }
})->with(selectPlaygroundKeys());

it('persists every select playground key via FlexFieldsPlaygroundStore', function (string $key): void {
    Cache::flush();
    auth()->login(new GenericUser(['id' => 77]));

    $defaults = selectPlaygroundDefaults();
    $store = new FlexFieldsPlaygroundStore;
    $slug = 'select-field';

    $payload = [$key => $defaults[$key]];
    $store->put($slug, $payload);

    expect($store->get($slug))->toBe($payload);

    $alternate = selectPlaygroundAlternate($key, $defaults[$key]);
    $store->put($slug, [$key => $alternate]);

    expect($store->get($slug))->toBe([$key => $alternate]);

    $store->forget($slug);
    expect($store->get($slug))->toBeNull();
})->with(selectPlaygroundKeys());

it('persists the full select playground defaultState blob', function (): void {
    Cache::flush();
    auth()->login(new GenericUser(['id' => 88]));

    $defaults = selectPlaygroundDefaults();
    $store = new FlexFieldsPlaygroundStore;

    $store->put('select-field', $defaults);
    expect($store->get('select-field'))->toBe($defaults);

    $mutated = $defaults;
    $mutated['select__basic'] = 'draft';
    $mutated['select__multiple'] = ['horror'];
    $mutated['select__cascade_country'] = 'pl';
    $mutated['select__cascade_region'] = 'mz';

    $store->put('select-field', $mutated);
    expect($store->get('select-field'))->toBe($mutated)
        ->and($store->get('select-field')['select__cascade_region'])->toBe('mz');
});

it('resolves getOptionsForJs for dynamic SelectField keys in playground', function (): void {
    $playground = app(SelectPlayground::class);
    $defaults = $playground->defaultState();

    TestableTranslatableForm::$formSchema = $playground->components();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->call('mountWithPlaygroundState', $defaults);

    $schema = $livewire->instance()->getSchema('form');
    $checked = 0;

    foreach (['select__dynamic_options', 'select__cascade_region', 'select__async_paginated'] as $statePath) {
        $component = $schema->getComponentByStatePath($statePath);

        expect($component)->toBeInstanceOf(SelectField::class);

        $options = $livewire->instance()->callSchemaComponentMethod($component->getKey(), 'getOptionsForJs');
        expect($options)->toBeArray();
        $checked++;
    }

    expect($checked)->toBe(3);
});

it('cascade region options flip with country without wiping sibling state', function (): void {
    TestableTranslatableForm::$formSchema = [
        Grid::make(['default' => 1, 'lg' => 2])->schema([
            SelectField::make('select__cascade_country')
                ->options([
                    'us' => 'United States',
                    'pl' => 'Poland',
                ])
                ->live()
                ->searchable(),
            SelectField::make('select__cascade_region')
                ->dependsOn('select__cascade_country', fn (?string $country): array => match ($country) {
                    'us' => [
                        'ca' => 'California',
                        'tx' => 'Texas',
                    ],
                    'pl' => [
                        'mz' => 'Mazowieckie',
                    ],
                    default => [],
                })
                ->searchable()
                ->placeholder('Pick a country first'),
        ]),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->set('data.select__cascade_country', 'us')
        ->set('data.select__cascade_region', 'ca')
        ->assertSet('data.select__cascade_country', 'us')
        ->assertSet('data.select__cascade_region', 'ca');

    $region = $livewire->instance()->getSchema('form')->getComponentByStatePath('select__cascade_region');
    expect(collect($livewire->instance()->callSchemaComponentMethod($region->getKey(), 'getOptionsForJs'))->pluck('value')->all())
        ->toContain('ca', 'tx')
        ->not->toContain('mz');

    $livewire->set('data.select__cascade_country', 'pl')
        ->set('data.select__cascade_region', null)
        ->assertSet('data.select__cascade_country', 'pl')
        ->assertSet('data.select__cascade_region', null);

    expect(collect($livewire->instance()->callSchemaComponentMethod($region->getKey(), 'getOptionsForJs'))->pluck('value')->all())
        ->toContain('mz')
        ->not->toContain('ca');

    $livewire->set('data.select__cascade_region', 'mz')
        ->assertSet('data.select__cascade_region', 'mz')
        ->assertSet('data.select__cascade_country', 'pl');
});

it('conflict hammer: alternating Livewire values stay consistent', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('hammer')
            ->options([
                'a' => 'A',
                'b' => 'B',
                'c' => 'C',
            ])
            ->live()
            ->searchable(),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);

    foreach (['a', 'b', 'c', null, 'a', 'c', 'b', null, 'a'] as $value) {
        $livewire->set('data.hammer', $value)
            ->assertSet('data.hammer', $value);
    }
});

it('conflict hammer multi: reorder and clear cycles', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('hammer_multi')
            ->options([
                'tailwind' => 'Tailwind',
                'laravel' => 'Laravel',
                'livewire' => 'Livewire',
                'alpine' => 'Alpine',
            ])
            ->multiple()
            ->reorderable()
            ->live()
            ->searchable(),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);

    foreach ([
        ['tailwind', 'laravel'],
        ['laravel', 'tailwind'],
        ['alpine', 'livewire', 'laravel', 'tailwind'],
        [],
        ['tailwind'],
        ['livewire', 'alpine'],
    ] as $value) {
        $livewire->set('data.hammer_multi', $value)
            ->assertSet('data.hammer_multi', $value);
    }
});

it('full playground defaultState keys are unique and stable', function (): void {
    $defaults = selectPlaygroundDefaults();
    $keys = array_keys($defaults);

    expect($keys)->toHaveCount(count(array_unique($keys)))
        ->and($keys)->toContain(
            'select__basic',
            'select__multiple',
            'select__reorderable',
            'select__entity_mentions',
            'select__dynamic_options',
            'select__cascade_country',
            'select__cascade_region',
            'select__create_single',
            'select__create_multiple',
            'select__create_with_sections',
            'select__scale_10k',
            'select__async_paginated',
            'select__clearable',
            'select__rtl',
        );
});
