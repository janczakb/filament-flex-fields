<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField;
use Bjanczak\FilamentFlexFields\Support\AudioWaveformGenerator;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

it('exposes audio field configuration api', function () {
    $field = AudioField::make('voice')
        ->size('lg')
        ->fullWidth()
        ->loop()
        ->waveform([20, 40, 60, 80]);

    expect($field->getSize())->toBe('lg')
        ->and($field->isFullWidth())->toBeTrue()
        ->and($field->shouldLoop())->toBeTrue()
        ->and($field->getWaveform())->toBe([20, 40, 60, 80]);
});

it('resolves audio source from static src or state', function () {
    $field = AudioField::make('voice')->src('https://example.com/static.mp3');

    expect($field->resolveAudioSrc('https://example.com/state.mp3'))
        ->toBe('https://example.com/static.mp3');

    $dynamic = AudioField::make('voice');

    expect($dynamic->resolveAudioSrc('https://example.com/state.mp3'))
        ->toBe('https://example.com/state.mp3')
        ->and($dynamic->resolveAudioSrc(null))->toBeNull();
});

it('sanitizes unsafe audio urls', function () {
    $field = AudioField::make('voice');

    expect($field->resolveAudioSrc('javascript:alert(1)'))->toBeNull()
        ->and(AudioField::make('voice')->src('data:audio/wav;base64,abc')->getSrc())->toBeNull();
});

it('rejects unsafe audio state during validation', function () {
    $field = AudioField::make('voice');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('voice', 'vbscript:msgbox(1)', function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.media.invalid_url'));
});

it('uses placeholder waveform when none is configured', function () {
    $field = AudioField::make('voice');

    expect($field->getWaveform())->toBe(AudioWaveformGenerator::placeholderWaveform())
        ->and($field->hasCustomWaveform())->toBeFalse();
});

it('resolves waveform fingerprint from audio source', function () {
    $field = AudioField::make('voice');

    $first = $field->resolveWaveform('https://example.com/voice.mp3');
    $second = $field->resolveWaveform('https://example.com/voice.mp3');
    $other = $field->resolveWaveform('https://example.com/other.mp3');

    expect($first)->toBe($second)
        ->and($first)->not->toBe($other)
        ->and($first)->toHaveCount(AudioWaveformGenerator::SAMPLE_COUNT);
});

it('prefers custom waveform over audio fingerprint', function () {
    $field = AudioField::make('voice')->waveform([20, 40, 60, 80]);

    expect($field->resolveWaveform('https://example.com/voice.mp3'))
        ->toBe([20, 40, 60, 80])
        ->and($field->hasCustomWaveform())->toBeTrue();
});

it('normalizes waveform peaks to safe bounds', function () {
    $field = AudioField::make('voice')->waveform([2, 50, 150]);

    expect($field->getWaveform())->toBe([8, 50, 100]);
});

it('rejects invalid waveform peaks', function () {
    AudioField::make('voice')->waveform(['bad'])->getWaveform();
})->throws(InvalidArgumentException::class);

it('includes wrapper classes for size', function () {
    $field = AudioField::make('voice')->size('sm');

    expect($field->getWrapperClasses())->toBe([
        'fff-audio-field-field',
        'fff-audio-field-field--sm',
    ]);
});

it('builds audio field from flex field definition', function () {
    $builder = new FlexFieldFormBuilder;
    $component = $builder->makeComponent(new FlexFieldDefinition(
        slug: 'voice',
        label: 'Voice',
        type: FieldType::Audio,
        config: [
            'size' => 'md',
            'full_width' => true,
            'loop' => true,
        ],
    ));

    expect($component)->toBeInstanceOf(AudioField::class)
        ->and($component->getSize())->toBe('md')
        ->and($component->isFullWidth())->toBeTrue()
        ->and($component->shouldLoop())->toBeTrue();
});

it('registers audio field playground variants', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state)->toHaveKeys([
        'audio__basic',
        'audio__fullwidth',
        'audio__sm',
        'audio__lg',
        'audio__custom_wave',
    ]);
});

it('uses gravity ui icons for audio controls by default', function () {
    $field = AudioField::make('voice');

    expect($field->getPlayIcon())->toBe(GravityIcon::PlayFill)
        ->and($field->getPauseIcon())->toBe(GravityIcon::PauseFill);
});

it('allows overriding audio control icons', function () {
    $field = AudioField::make('voice')
        ->playIcon(Heroicon::OutlinedPlay)
        ->pauseIcon(Heroicon::OutlinedPause);

    expect($field->getPlayIcon())->toBe(Heroicon::OutlinedPlay)
        ->and($field->getPauseIcon())->toBe(Heroicon::OutlinedPause);
});
