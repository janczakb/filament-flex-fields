@php
    use Filament\Support\Enums\IconSize;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = false;
    $wrapperClasses = $getWrapperClasses();
    $initialAudioUrl = $field->getInitialAudioUrl();
    $livewireKey = $getLivewireKey();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize()])), 0, 64) }}"
        @class([
            'fff-voice-recorder',
            'fff-voice-recorder--'.$getSize(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'voice-note-recorder-field'])

        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('voice-note-recorder-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
            x-data="voiceNoteRecorderFieldFormComponent({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                statePath: @js($statePath),
                schemaComponentKey: @js($getKey()),
                initialAudioUrl: @js($initialAudioUrl),
                maxDuration: @js($getMaxDuration()),
                uploadImmediately: @js($field->shouldUploadImmediately()),
                readOnly: @js($isDisabled || $isReadOnly),
                labels: {
                    uploadingOnSubmit: @js(__('filament-flex-fields::default.audio.uploading_on_submit')),
                },
            })"
            x-init="init()"
            x-bind:class="{ 'is-submitting': isUploadingForSubmit }"
            class="fff-voice-recorder__container"
        >
            <!-- Tryb Idle (Brak nagrania) -->
            <div x-show="mode === 'idle'" class="fff-voice-recorder__idle">
                <button
                    type="button"
                    class="fff-voice-recorder__record-btn"
                    x-on:click="startRecording()"
                    x-bind:disabled="readOnly"
                    title="{{ __('filament-flex-fields::default.audio.record_start') }}"
                >
                    <span class="fff-voice-recorder__icon-mic">
                        {{ \Filament\Support\generate_icon_html($field->getMicrophoneIcon(), size: IconSize::Small) }}
                    </span>
                </button>
                <span class="fff-voice-recorder__label" x-on:click="startRecording()">
                    {{ __('filament-flex-fields::default.audio.record_label') }}
                </span>
            </div>

            <!-- Tryb Recording (Nagrywanie) -->
            <div x-show="mode === 'recording'" x-cloak class="fff-voice-recorder__recording">
                <div class="fff-voice-recorder__recording-status">
                    <span class="fff-voice-recorder__pulse-dot"></span>
                    <span class="fff-voice-recorder__timer" x-text="formattedDuration">0:00 / 2:00</span>
                </div>

                <div class="fff-voice-recorder__canvas-wrapper">
                    <canvas x-ref="canvas" class="fff-voice-recorder__canvas"></canvas>
                </div>

                <div class="fff-voice-recorder__actions">
                    <!-- Anuluj -->
                    <button
                        type="button"
                        class="fff-voice-recorder__action-btn fff-voice-recorder__action-btn--cancel"
                        x-on:click="cancelRecording()"
                        title="{{ __('filament-flex-fields::default.audio.cancel') }}"
                    >
                        {{ \Filament\Support\generate_icon_html($field->getTrashIcon(), size: IconSize::Small) }}
                    </button>
                    <!-- Stop i zapisz -->
                    <button
                        type="button"
                        class="fff-voice-recorder__action-btn fff-voice-recorder__action-btn--stop"
                        x-on:click="stopRecording()"
                        title="{{ __('filament-flex-fields::default.audio.save') }}"
                    >
                        {{ \Filament\Support\generate_icon_html($field->getCheckmarkIcon(), size: IconSize::Small) }}
                    </button>
                </div>
            </div>

            <!-- Tryb Uploading (Wysyłanie) -->
            <div x-show="mode === 'uploading'" x-cloak class="fff-voice-recorder__uploading">
                <div class="fff-voice-recorder__spinner-wrapper">
                    <svg class="fff-voice-recorder__spinner animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <span class="fff-voice-recorder__upload-label">
                    <span x-show="isUploadingForSubmit" x-cloak>{{ __('filament-flex-fields::default.audio.uploading_on_submit') }}</span>
                    <span x-show="! isUploadingForSubmit">{{ __('filament-flex-fields::default.audio.uploading') }} <span x-text="uploadProgress + '%'">0%</span></span>
                </span>
            </div>

            <!-- Tryb Playback (Odtwarzanie) -->
            <div x-show="mode === 'playback'" x-cloak class="fff-voice-recorder__playback">
                <div class="fff-voice-recorder__playback-pill">
                    <button
                        type="button"
                        class="fff-voice-recorder__play-btn"
                        x-on:click="togglePlay()"
                    >
                        <span class="fff-voice-recorder__icon-play" x-show="!playing">
                            {{ \Filament\Support\generate_icon_html($field->getPlayIcon(), size: IconSize::ExtraSmall) }}
                        </span>
                        <span class="fff-voice-recorder__icon-pause" x-show="playing" x-cloak>
                            {{ \Filament\Support\generate_icon_html($field->getPauseIcon(), size: IconSize::ExtraSmall) }}
                        </span>
                    </button>

                    <!-- Waveform zbieżny z audio-field -->
                    <div
                        class="fff-voice-recorder__waveform"
                        x-ref="waveform"
                        x-on:pointerdown="onWaveformPointerDown($event)"
                        role="slider"
                        x-bind:aria-valuenow="Math.round(progressRatio * 100)"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <!-- Szkielet animacji -->
                        <div class="fff-voice-recorder__waveform-skeleton" aria-hidden="true" x-show="!waveformReady">
                            @for ($skeletonIndex = 0; $skeletonIndex < 30; $skeletonIndex++)
                                <span class="fff-voice-recorder__skeleton-bar" style="--fff-audio-skeleton-i: {{ $skeletonIndex }}"></span>
                            @endfor
                            <span class="fff-voice-recorder__waveform-skeleton-shimmer"></span>
                        </div>

                        <!-- Generowane słupki waveform -->
                        <template x-for="(peak, index) in displayWaveform" :key="index">
                            <span
                                class="fff-voice-recorder__bar"
                                x-bind:style="'height: ' + peak + '%; --fff-audio-bar-i: ' + index"
                                x-bind:class="{ 'is-played': barIsPlayed(index) }"
                            ></span>
                        </template>
                    </div>

                    <span class="fff-voice-recorder__time" x-text="timeLabel">0:00</span>

                    <span
                        class="fff-voice-recorder__bg-upload"
                        x-show="isBackgroundUploading"
                        x-cloak
                        x-text="uploadProgress + '%'"
                    ></span>

                    <!-- Usuń / Nagraj ponownie -->
                    <button
                        type="button"
                        class="fff-voice-recorder__delete-btn"
                        x-on:click="deleteRecording()"
                        x-bind:disabled="readOnly"
                        title="{{ __('filament-flex-fields::default.audio.delete') }}"
                    >
                        {{ \Filament\Support\generate_icon_html($field->getTrashIcon(), size: IconSize::ExtraSmall) }}
                    </button>
                </div>

            </div>

            <audio
                x-ref="audio"
                class="sr-only"
                preload="metadata"
                x-bind:src="audioSrc"
            ></audio>
        </div>
    </div>
</x-dynamic-component>
