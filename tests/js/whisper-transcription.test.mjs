import assert from 'node:assert/strict';
import test from 'node:test';

import {
    audioBufferToWhisperSamples,
    modelSupportsMultilingual,
    resolveWhisperModelId,
} from '../../resources/js/core/whisper-transcription.js';

test('resolveWhisperModelId prefers english variant when multilingual is disabled', () => {
    const models = [
        { id: 'Xenova/whisper-tiny', multilingual: true },
        { id: 'Xenova/whisper-tiny.en', multilingual: false },
    ];

    assert.equal(
        resolveWhisperModelId('Xenova/whisper-tiny', false, models),
        'Xenova/whisper-tiny.en',
    );
});

test('resolveWhisperModelId keeps distil model ids unchanged', () => {
    assert.equal(
        resolveWhisperModelId('distil-whisper/distil-medium.en', false, []),
        'distil-whisper/distil-medium.en',
    );
});

test('resolveWhisperModelId strips .en suffix when multilingual is enabled', () => {
    assert.equal(
        resolveWhisperModelId('Xenova/whisper-tiny.en', true, []),
        'Xenova/whisper-tiny',
    );
});

test('modelSupportsMultilingual reads model metadata when available', () => {
    const models = [
        { id: 'Xenova/whisper-tiny.en', multilingual: false },
    ];

    assert.equal(modelSupportsMultilingual('Xenova/whisper-tiny.en', models), false);
    assert.equal(modelSupportsMultilingual('Xenova/whisper-small', models), true);
});

test('audioBufferToWhisperSamples mixes stereo channels like whisper-web', () => {
    const audioBuffer = {
        numberOfChannels: 2,
        length: 2,
        getChannelData(channel) {
            return channel === 0 ? Float32Array.from([0.5, -0.5]) : Float32Array.from([0.5, 0.5]);
        },
    };

    const samples = audioBufferToWhisperSamples(audioBuffer);

    assert.ok(Math.abs(samples[0] - (Math.sqrt(2) / 2)) < 1e-5);
    assert.equal(samples[1], 0);
});
