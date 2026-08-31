<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Playground\FlexFieldsPlaygroundStore;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Cache;

it('stores and retrieves playground state by slug and user', function (): void {
    Cache::flush();

    $store = new FlexFieldsPlaygroundStore;

    auth()->login(new GenericUser(['id' => 42]));

    $store->put('signature-field', [
        'signature__enterprise' => 'ffstage:signatures/demo.svg',
        '_meta' => ['sealed_at' => '2026-08-31T00:00:00+00:00'],
    ]);

    expect($store->get('signature-field'))
        ->toBe([
            'signature__enterprise' => 'ffstage:signatures/demo.svg',
            '_meta' => ['sealed_at' => '2026-08-31T00:00:00+00:00'],
        ])
        ->and($store->key('signature-field'))->toBe('flex-fields-playground.signature-field.42');

    $store->forget('signature-field');

    expect($store->get('signature-field'))->toBeNull();
});
