<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Console\PruneCaptureMediaCommand;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\Media\SpatieMediaCaptureAdapter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    MediaCaptureOs::resetRuntimeState();
    Storage::fake('local');
    config([
        'filament-flex-fields.media_capture.disk' => 'local',
        'filament-flex-fields.media_capture.directories' => [
            'temp_captures' => ['livewire-tmp'],
            'voice_notes' => ['voice-notes'],
        ],
        'filament-flex-fields.media_capture.retention' => [
            'temp_captures' => [
                'enabled' => true,
                'days' => 7,
            ],
            'voice_notes' => [
                'enabled' => false,
                'days' => 365,
            ],
        ],
    ]);
});

it('prunes expired temp capture files from disk', function () {
    Storage::disk('local')->put('livewire-tmp/old.tmp', 'x');
    Storage::disk('local')->put('livewire-tmp/new.tmp', 'x');

    touch(Storage::disk('local')->path('livewire-tmp/old.tmp'), now()->subDays(10)->getTimestamp());
    touch(Storage::disk('local')->path('livewire-tmp/new.tmp'), now()->subDay()->getTimestamp());

    Artisan::call('flex-fields:prune-capture-media', ['--category' => ['temp_captures']]);

    expect(Storage::disk('local')->exists('livewire-tmp/old.tmp'))->toBeFalse()
        ->and(Storage::disk('local')->exists('livewire-tmp/new.tmp'))->toBeTrue();
});

it('supports dry run without deleting files', function () {
    Storage::disk('local')->put('livewire-tmp/old.tmp', 'x');
    touch(Storage::disk('local')->path('livewire-tmp/old.tmp'), now()->subDays(10)->getTimestamp());

    Artisan::call('flex-fields:prune-capture-media', [
        '--category' => ['temp_captures'],
        '--dry-run' => true,
    ]);

    expect(Storage::disk('local')->exists('livewire-tmp/old.tmp'))->toBeTrue();
});

it('skips disabled retention categories', function () {
    Storage::disk('local')->put('voice-notes/old.webm', 'audio');
    touch(Storage::disk('local')->path('voice-notes/old.webm'), now()->subDays(400)->getTimestamp());

    Artisan::call('flex-fields:prune-capture-media', ['--category' => ['voice_notes']]);

    expect(Storage::disk('local')->exists('voice-notes/old.webm'))->toBeTrue();
});

it('only considers spatie media stamped with flex_capture for prune', function () {
    $withStamp = new class
    {
        public string $uuid = 'capture-uuid';

        /** @var array<string, mixed> */
        public array $custom_properties = [
            'flex_capture' => ['field' => 'attachment', 'collection' => 'documents'],
        ];

        public function getAttributeValue(string $key): mixed
        {
            return match ($key) {
                'uuid' => $this->uuid,
                'custom_properties' => $this->custom_properties,
                default => null,
            };
        }

        public function delete(): void {}
    };

    $withoutStamp = new class
    {
        public string $uuid = 'other-uuid';

        /** @var array<string, mixed> */
        public array $custom_properties = [];

        public function getAttributeValue(string $key): mixed
        {
            return match ($key) {
                'uuid' => $this->uuid,
                'custom_properties' => $this->custom_properties,
                default => null,
            };
        }

        public function delete(): void {}
    };

    expect(SpatieMediaCaptureAdapter::hasFlexCaptureStamp($withStamp))->toBeTrue()
        ->and(SpatieMediaCaptureAdapter::hasFlexCaptureStamp($withoutStamp))->toBeFalse();
});

it('registers prune capture media command', function () {
    expect(class_exists(PruneCaptureMediaCommand::class))->toBeTrue();
});
