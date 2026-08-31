<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Geocoding\CircuitBreakerGeocodingProvider;
use Bjanczak\FilamentFlexFields\Support\Geocoding\GeocodingOs;
use Bjanczak\FilamentFlexFields\Support\Geocoding\GeocodingProviderContract;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    GeocodingOs::reset();
    Cache::flush();
});

it('opens the geocoding circuit breaker after repeated failures', function () {
    $failing = new class implements GeocodingProviderContract
    {
        public function search(array $options): array
        {
            throw new RuntimeException('upstream down');
        }

        public function reverse(array $options): array
        {
            throw new RuntimeException('upstream down');
        }
    };

    GeocodingOs::registerProvider(new CircuitBreakerGeocodingProvider($failing, 2, 30));

    expect(GeocodingOs::provider()->search(['query' => 'Paris'])['error'])->toBe('upstream down')
        ->and(GeocodingOs::provider()->search(['query' => 'Paris'])['error'])->toBe('upstream down')
        ->and(GeocodingOs::provider()->search(['query' => 'Paris'])['error'])->toBe(__('filament-flex-fields::default.geocoding.circuit_open'));
});

it('registers capture retention schedule when enabled', function () {
    config(['filament-flex-fields.media_capture.retention.schedule_enabled' => true]);

    $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

    expect(collect($schedule->events())->contains(
        fn ($event) => str_contains((string) ($event->command ?? ''), 'flex-fields:prune-capture-media'),
    ))->toBeTrue();
});

it('resolves tenant scoped upload directories from config prefix', function () {
    config(['filament-flex-fields.media_capture.tenant.directory_prefix' => 'tenant-42']);

    $directory = Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureTenantDiskResolver::resolveDirectory('voice-notes');

    expect($directory)->toStartWith('tenant-42/voice-notes/');
});

it('falls back to storeMetadataIn for voice note waveform path', function () {
    $field = Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField::make('voice')
        ->storeMetadataIn('voice_meta');

    expect($field->getStoreWaveformInPath())->toBe('voice_meta');
});
