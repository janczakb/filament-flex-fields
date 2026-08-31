import assert from 'node:assert/strict';
import test from 'node:test';

import {
    filterWhisperModels,
    formatWhisperModelLabel,
    pickWhisperModelSelection,
} from '../../resources/js/core/whisper-model-catalog.js';

const catalog = [
    { id: 'Xenova/whisper-tiny', multilingual: true, distil: false, sizes: [41, 152] },
    { id: 'Xenova/whisper-base', multilingual: true, distil: false, sizes: [77, 291] },
    { id: 'Xenova/whisper-small', multilingual: true, distil: false, sizes: [249] },
    { id: 'Xenova/whisper-medium', multilingual: true, distil: false, sizes: [776] },
    { id: 'distil-whisper/distil-medium.en', multilingual: false, distil: true, sizes: [402] },
    { id: 'distil-whisper/distil-large-v2', multilingual: false, distil: true, sizes: [767] },
];

test('default multilingual + non-quantized shows tiny and base full models', () => {
    const visible = filterWhisperModels(catalog, true, false);

    assert.deepEqual(
        visible.map((model) => model.id),
        ['Xenova/whisper-tiny', 'Xenova/whisper-base'],
    );

    assert.equal(
        formatWhisperModelLabel(visible[0], true, false),
        'Xenova/whisper-tiny (152 MB)',
    );
});

test('english quantized shows all english and distil models', () => {
    const visible = filterWhisperModels(catalog, false, true);

    assert.deepEqual(
        visible.map((model) => model.id),
        [
            'Xenova/whisper-tiny',
            'Xenova/whisper-base',
            'Xenova/whisper-small',
            'Xenova/whisper-medium',
            'distil-whisper/distil-medium.en',
            'distil-whisper/distil-large-v2',
        ],
    );

    assert.equal(
        formatWhisperModelLabel(visible[0], false, true),
        'Xenova/whisper-tiny.en (41 MB)',
    );
});

test('multilingual quantized shows four xenova models without distil', () => {
    const visible = filterWhisperModels(catalog, true, true);

    assert.deepEqual(
        visible.map((model) => model.id),
        [
            'Xenova/whisper-tiny',
            'Xenova/whisper-base',
            'Xenova/whisper-small',
            'Xenova/whisper-medium',
        ],
    );

    assert.equal(
        formatWhisperModelLabel(visible[3], true, true),
        'Xenova/whisper-medium (776 MB)',
    );
});

test('pickWhisperModelSelection normalizes legacy .en ids', () => {
    assert.equal(
        pickWhisperModelSelection(catalog, 'Xenova/whisper-tiny.en', true, false),
        'Xenova/whisper-tiny',
    );
});
