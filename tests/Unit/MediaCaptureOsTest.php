<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Media\CircuitBreakerTranscriptionProvider;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\Media\VoiceNoteTranscriptionInterface;

beforeEach(function () {
    MediaCaptureOs::resetRuntimeState();
});

it('registers and resolves media capture hooks', function () {
    $virusScan = fn (string $path): bool => str_ends_with($path, '.pdf');
    $signedUrl = fn (string $disk, string $path, array $context): string => "https://example.test/{$disk}/{$path}";
    $tokenize = fn (string $pan): string => 'tok_'.strlen($pan);

    MediaCaptureOs::registerVirusScanCallback($virusScan);
    MediaCaptureOs::registerSignedUploadUrlResolver($signedUrl);
    MediaCaptureOs::registerTokenizeCreditCardCallback($tokenize);

    expect(MediaCaptureOs::virusScanCallback())->toBe($virusScan)
        ->and(MediaCaptureOs::signedUploadUrlResolver())->toBe($signedUrl)
        ->and(MediaCaptureOs::tokenizeCreditCardCallback())->toBe($tokenize)
        ->and(MediaCaptureOs::virusScanCallback()('invoice.pdf'))->toBeTrue()
        ->and(MediaCaptureOs::signedUploadUrlResolver()('uploads', 'voice/a.webm', []))->toBe('https://example.test/uploads/voice/a.webm')
        ->and(MediaCaptureOs::tokenizeCreditCardCallback()('4111111111111111'))->toBe('tok_16');
});

it('registers voice note transcription interface stub', function () {
    $transcriber = new class implements VoiceNoteTranscriptionInterface
    {
        public function transcribe(string $disk, string $path, array $context = []): ?string
        {
            return "transcript:{$disk}/{$path}";
        }
    };

    MediaCaptureOs::registerTranscriptionInterface($transcriber);

    expect(MediaCaptureOs::transcriptionInterface())
        ->toBeInstanceOf(VoiceNoteTranscriptionInterface::class)
        ->and(MediaCaptureOs::transcriptionInterface()?->transcribe('local', 'notes/a.webm'))
        ->toBe('transcript:local/notes/a.webm');
});

it('exposes retention policy defaults for capture fields', function () {
    expect(MediaCaptureOs::retentionPolicyDefaults())->toMatchArray([
        'uploads' => [
            'enabled' => false,
            'days' => null,
        ],
        'voice_notes' => [
            'enabled' => false,
            'days' => 365,
        ],
        'signatures' => [
            'enabled' => false,
            'days' => null,
        ],
        'temp_captures' => [
            'enabled' => true,
            'days' => 7,
        ],
    ]);
});

it('boots permissive virus scan default only when scan is not required', function () {
    config(['filament-flex-fields.media_capture.require_virus_scan' => false]);
    MediaCaptureOs::bootSafeDefaults();

    expect(MediaCaptureOs::virusScanCallback())
        ->not->toBeNull()
        ->and(MediaCaptureOs::passesVirusScan('invoice.pdf'))->toBeTrue();
});

it('fail-closes virus scan when required and no host scanner is registered', function () {
    config(['filament-flex-fields.media_capture.require_virus_scan' => true]);
    MediaCaptureOs::bootFromConfig();

    expect(MediaCaptureOs::virusScanCallback())->toBeNull()
        ->and(MediaCaptureOs::passesVirusScan('invoice.pdf'))->toBeFalse();
});

it('boots retention and transcription bindings from config', function () {
    $transcriber = new class implements VoiceNoteTranscriptionInterface
    {
        public function transcribe(string $disk, string $path, array $context = []): ?string
        {
            return 'ok';
        }
    };

    $class = $transcriber::class;
    app()->instance($class, $transcriber);

    config([
        'filament-flex-fields.media_capture' => [
            'transcription' => $class,
            'retention' => [
                'voice_notes' => [
                    'enabled' => true,
                    'days' => 30,
                ],
            ],
        ],
    ]);

    MediaCaptureOs::bootFromConfig();

    expect(MediaCaptureOs::transcriptionInterface())
        ->toBeInstanceOf(CircuitBreakerTranscriptionProvider::class)
        ->and(MediaCaptureOs::transcriptionInterface()?->transcribe('local', 'notes/a.webm'))
        ->toBe('ok')
        ->and(MediaCaptureOs::retentionPolicies()['voice_notes'])->toMatchArray([
            'enabled' => true,
            'days' => 30,
        ]);
});
