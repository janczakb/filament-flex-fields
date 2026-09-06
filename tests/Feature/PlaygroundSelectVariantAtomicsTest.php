<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexFieldsPlaygroundStore;
use Bjanczak\FilamentFlexFields\Tests\Support\SelectPayloadPost;
use Bjanczak\FilamentFlexFields\Tests\Support\SelectPlaygroundVariantRegistry;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Schema::dropIfExists('select_payload_posts');

    Schema::create('select_payload_posts', function (Blueprint $table): void {
        $table->id();
        $table->json('payload')->nullable();
        $table->timestamps();
    });
});

/**
 * @param  array<string, mixed>  $variant
 */
function selectVariantField(string $key, array $variant): SelectField
{
    $field = SelectField::make($key)
        ->label($variant['label'])
        ->options($variant['options'])
        ->multiple($variant['multiple'])
        ->clearable($variant['clearable'])
        ->searchable($variant['searchable']);

    if ($variant['live'] || str_starts_with(($variant['flags'][0] ?? ''), 'live') || in_array('live', $variant['flags'] ?? [], true)) {
        $field->live();
    } else {
        $field->live(); // always live for wire-sync atomics — mirrors playground cascade/create demos
    }

    $flags = $variant['flags'] ?? [];

    if (in_array('reorderable', $flags, true)) {
        $field->reorderable();
    }

    if (in_array('checklist', $flags, true)) {
        $field->keepSelectedOptionsInDropdown();
    }

    if (in_array('inline_search', $flags, true)) {
        $field->inlineSearch();
    }

    if (in_array('inline_field_label', $flags, true)) {
        $field->inlineFieldLabel();
    }

    if (in_array('allow_create', $flags, true)) {
        $field->allowCreateOption();
    }

    if (in_array('required', $flags, true)) {
        $field->required();
    }

    if (in_array('boolean', $flags, true)) {
        $field->boolean();
    }

    foreach ($flags as $flag) {
        if (str_starts_with($flag, 'size:')) {
            $field->size(substr($flag, 5));
        }

        if (str_starts_with($flag, 'variant:')) {
            $field->variant(substr($flag, 8));
        }
    }

    if (in_array('disabled_field', $flags, true)) {
        $field->disabled();
    }

    return $field;
}

function selectVariantLivewire(string $key): Testable
{
    $variant = SelectPlaygroundVariantRegistry::get($key);
    TestableTranslatableForm::$formSchema = [selectVariantField($key, $variant)];

    return Livewire::test(TestableTranslatableForm::class);
}

function selectVariantEmpty(array $variant): mixed
{
    return $variant['empty'];
}

function selectVariantIsEmpty(mixed $value, array $variant): bool
{
    if ($variant['multiple']) {
        return is_array($value) && $value === [];
    }

    return $value === null || $value === '';
}

it('select playground variant atomic matrix', function (string $key, string $scenario): void {
    $variant = SelectPlaygroundVariantRegistry::get($key);
    $path = 'data.'.$key;
    $filled = $variant['filled'];
    $alternate = $variant['alternate'];
    $empty = selectVariantEmpty($variant);
    $clearable = $variant['clearable'] && ! in_array('disabled_field', $variant['flags'] ?? [], true);

    if (str_starts_with($scenario, 'lifecycle_filled_')) {
        selectVariantLivewire($key)
            ->set($path, $filled)
            ->assertSet($path, $filled);

        return;
    }

    if (str_starts_with($scenario, 'lifecycle_clear_empty_')) {
        $livewire = selectVariantLivewire($key)
            ->set($path, $filled)
            ->assertSet($path, $filled);

        // × clear (or programmatic empty): must land as empty wire state
        $livewire->set($path, $empty);
        $actual = $livewire->get($path);
        expect(selectVariantIsEmpty($actual, $variant))->toBeTrue();

        if ($clearable) {
            expect($actual)->toEqual($empty);
        }

        return;
    }

    if (str_starts_with($scenario, 'lifecycle_refill_after_clear_')) {
        selectVariantLivewire($key)
            ->set($path, $filled)
            ->set($path, $empty)
            ->set($path, $alternate)
            ->assertSet($path, $alternate)
            ->set($path, $empty);

        $actual = selectVariantLivewire($key)
            ->set($path, $filled)
            ->set($path, $empty)
            ->get($path);

        expect(selectVariantIsEmpty($actual, $variant))->toBeTrue();

        return;
    }

    if (str_starts_with($scenario, 'select_option_')) {
        $option = substr($scenario, strlen('select_option_'));
        $value = $variant['multiple'] ? [$option] : (is_numeric($option) && ($variant['flags'][0] ?? '') === 'boolean' ? (bool) $option : $option);

        if (in_array('boolean', $variant['flags'] ?? [], true) && ! $variant['multiple']) {
            $value = match ($option) {
                '1', 1 => true,
                '0', 0 => false,
                default => $option,
            };
        }

        selectVariantLivewire($key)
            ->set($path, $value)
            ->assertSet($path, $value);

        return;
    }

    if (str_starts_with($scenario, 'clear_from_option_')) {
        $option = substr($scenario, strlen('clear_from_option_'));
        $value = $variant['multiple'] ? [$option] : $option;

        if (in_array('boolean', $variant['flags'] ?? [], true) && ! $variant['multiple']) {
            $value = match ((string) $option) {
                '1' => true,
                '0' => false,
                default => $option,
            };
        }

        $actual = selectVariantLivewire($key)
            ->set($path, $value)
            ->set($path, $empty)
            ->get($path);

        expect(selectVariantIsEmpty($actual, $variant))->toBeTrue();

        return;
    }

    if (str_starts_with($scenario, 'db_persist_option_')) {
        $option = substr($scenario, strlen('db_persist_option_'));
        $value = $variant['multiple'] ? [$option] : $option;

        if (in_array('boolean', $variant['flags'] ?? [], true) && ! $variant['multiple']) {
            $value = match ((string) $option) {
                '1' => true,
                '0' => false,
                default => $option,
            };
        }

        $livewire = selectVariantLivewire($key)
            ->set($path, $value);

        $post = SelectPayloadPost::query()->create([
            'payload' => [$key => $livewire->get($path)],
        ]);

        expect($post->fresh()->payload[$key])->toEqual($value);

        return;
    }

    if (str_starts_with($scenario, 'db_clear_empty_from_')) {
        $option = substr($scenario, strlen('db_clear_empty_from_'));
        $value = $variant['multiple'] ? [$option] : $option;

        if (in_array('boolean', $variant['flags'] ?? [], true) && ! $variant['multiple']) {
            $value = match ((string) $option) {
                '1' => true,
                '0' => false,
                default => $option,
            };
        }

        $livewire = selectVariantLivewire($key)
            ->set($path, $value)
            ->set($path, $empty);

        $post = SelectPayloadPost::query()->create([
            'payload' => [$key => $livewire->get($path)],
        ]);

        expect(selectVariantIsEmpty($post->fresh()->payload[$key], $variant))->toBeTrue();

        // reload form from DB empty payload
        $reloaded = Livewire::test(TestableTranslatableForm::class)
            ->set($path, $post->fresh()->payload[$key]);

        expect(selectVariantIsEmpty($reloaded->get($path), $variant))->toBeTrue();

        return;
    }

    if (str_starts_with($scenario, 'store_persist_option_')) {
        Cache::flush();
        auth()->login(new GenericUser(['id' => 501]));
        $option = substr($scenario, strlen('store_persist_option_'));
        $value = $variant['multiple'] ? [$option] : $option;

        if (in_array('boolean', $variant['flags'] ?? [], true) && ! $variant['multiple']) {
            $value = match ((string) $option) {
                '1' => true,
                '0' => false,
                default => $option,
            };
        }

        $store = new FlexFieldsPlaygroundStore;
        $store->put('select-field', [$key => $value]);
        expect($store->get('select-field')[$key])->toEqual($value);

        return;
    }

    if (str_starts_with($scenario, 'store_clear_empty_from_')) {
        Cache::flush();
        auth()->login(new GenericUser(['id' => 502]));
        $option = substr($scenario, strlen('store_clear_empty_from_'));
        $value = $variant['multiple'] ? [$option] : $option;

        if (in_array('boolean', $variant['flags'] ?? [], true) && ! $variant['multiple']) {
            $value = match ((string) $option) {
                '1' => true,
                '0' => false,
                default => $option,
            };
        }

        $store = new FlexFieldsPlaygroundStore;
        $store->put('select-field', [$key => $value]);
        $store->put('select-field', [$key => $empty]);
        expect(selectVariantIsEmpty($store->get('select-field')[$key], $variant))->toBeTrue();

        return;
    }

    if (str_starts_with($scenario, 'replace_')) {
        // replace_{from}_to_{to}
        $body = substr($scenario, strlen('replace_'));
        [$from, $to] = explode('_to_', $body, 2);
        $fromValue = $variant['multiple'] ? [$from] : $from;
        $toValue = $variant['multiple'] ? [$to] : $to;

        selectVariantLivewire($key)
            ->set($path, $fromValue)
            ->assertSet($path, $fromValue)
            ->set($path, $toValue)
            ->assertSet($path, $toValue);

        return;
    }

    if (str_starts_with($scenario, 'conflict_hammer_')) {
        $livewire = selectVariantLivewire($key);
        $sequence = [$filled, $alternate, $empty, $filled, $empty, $alternate, $filled, $empty];

        foreach ($sequence as $step) {
            $livewire->set($path, $step);

            if (selectVariantIsEmpty($step, $variant)) {
                expect(selectVariantIsEmpty($livewire->get($path), $variant))->toBeTrue();
            } else {
                $livewire->assertSet($path, $step);
            }
        }

        return;
    }

    if (str_starts_with($scenario, 'live_clear_cycle_')) {
        $livewire = selectVariantLivewire($key);

        for ($i = 0; $i < 5; $i++) {
            $livewire->set($path, $i % 2 === 0 ? $filled : $alternate)
                ->set($path, $empty);
            expect(selectVariantIsEmpty($livewire->get($path), $variant))->toBeTrue();
        }

        $livewire->set($path, $filled)->assertSet($path, $filled);

        return;
    }

    if (str_starts_with($scenario, 'multi_reorder_')) {
        expect($variant['multiple'])->toBeTrue();
        $orderA = array_values(array_map(strval(...), array_keys($variant['options'])));
        $orderB = array_values(array_reverse($orderA));

        selectVariantLivewire($key)
            ->set($path, $orderA)
            ->assertSet($path, $orderA)
            ->set($path, $orderB)
            ->assertSet($path, $orderB)
            ->set($path, [])
            ->assertSet($path, []);

        return;
    }

    if (str_starts_with($scenario, 'multi_partial_then_clear_')) {
        expect($variant['multiple'])->toBeTrue();
        $all = array_values(array_map(strval(...), array_keys($variant['options'])));
        $partial = array_slice($all, 0, max(1, (int) floor(count($all) / 2)));

        selectVariantLivewire($key)
            ->set($path, $all)
            ->set($path, $partial)
            ->assertSet($path, $partial)
            ->set($path, [])
            ->assertSet($path, []);

        return;
    }

    if (str_starts_with($scenario, 'cascade_clear_')) {
        // Covered deeply in dedicated cascade suite — still assert empty write for these keys.
        selectVariantLivewire($key)
            ->set($path, $filled)
            ->set($path, $empty);

        expect(selectVariantIsEmpty(selectVariantLivewire($key)->set($path, $filled)->set($path, $empty)->get($path), $variant))->toBeTrue();

        return;
    }

    throw new InvalidArgumentException("Unhandled scenario [{$scenario}] for [{$key}].");
})->with(SelectPlaygroundVariantRegistry::dataset());

it('clearable variants expose clearable API; not_clearable hides UI clear', function (string $key): void {
    $variant = SelectPlaygroundVariantRegistry::get($key);
    $field = selectVariantField($key, $variant);

    if (in_array('not_clearable', $variant['flags'] ?? [], true) || $variant['clearable'] === false) {
        expect($field->isClearable())->toBeFalse();
    } else {
        expect($field->isClearable())->toBeTrue();
    }
})->with(SelectPlaygroundVariantRegistry::keys());

it('× clear empty state persists to eloquence for every clearable playground variant', function (string $key): void {
    $variant = SelectPlaygroundVariantRegistry::get($key);

    if (! $variant['clearable'] || in_array('disabled_field', $variant['flags'] ?? [], true)) {
        expect(true)->toBeTrue();

        return;
    }

    $path = 'data.'.$key;
    $livewire = selectVariantLivewire($key)
        ->set($path, $variant['filled'])
        ->assertSet($path, $variant['filled'])
        ->set($path, $variant['empty']);

    expect(selectVariantIsEmpty($livewire->get($path), $variant))->toBeTrue();

    $post = SelectPayloadPost::query()->create([
        'payload' => [$key => $livewire->get($path)],
    ]);

    expect(selectVariantIsEmpty($post->fresh()->payload[$key], $variant))->toBeTrue();

    // second session: hydrate empty from DB, ensure it stays empty (no SSR re-fill ghost)
    $again = selectVariantLivewire($key)
        ->set($path, $post->fresh()->payload[$key]);

    expect(selectVariantIsEmpty($again->get($path), $variant))->toBeTrue();
})->with(SelectPlaygroundVariantRegistry::keys());
