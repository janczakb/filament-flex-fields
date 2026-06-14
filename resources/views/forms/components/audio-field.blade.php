@php
    use Filament\Support\Enums\IconSize;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $audioSrc = $field->resolveAudioSrc($getState());
    $waveform = $field->resolveWaveform($getState());
    $waveformIsCustom = $field->hasCustomWaveform();
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
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $getSrc(), $audioSrc, $waveformIsCustom])), 0, 64) }}"
        @class([
            'fff-audio-field',
            'fff-audio-field--'.$getSize(),
            'is-full-width' => $isFullWidth(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'is-empty' => blank($audioSrc),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'audio-field'])
        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('audio-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
            x-data="audioFieldFormComponent({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                staticSrc: @js($getSrc()),
                waveform: @js($waveform),
                waveformIsCustom: @js($waveformIsCustom),
                loop: @js($shouldLoop()),
                readOnly: @js($isDisabled || $isReadOnly),
                labels: {
                    play: @js(__('filament-flex-fields::default.audio.play')),
                    pause: @js(__('filament-flex-fields::default.audio.pause')),
                },
            })"
            x-init="init()"
            class="fff-audio-field__player"
        >
            <div class="fff-audio-field__pill">
                <button
                    type="button"
                    class="fff-audio-field__play"
                    x-on:click="togglePlay()"
                    x-bind:aria-label="playing ? labels.pause : labels.play"
                    x-bind:disabled="! canInteract"
                >
                    <span class="fff-audio-field__icon-play" x-show="! playing" aria-hidden="true">
                        {{ \Filament\Support\generate_icon_html($field->getPlayIcon(), size: IconSize::ExtraSmall) }}
                    </span>
                    <span class="fff-audio-field__icon-pause" x-show="playing" x-cloak aria-hidden="true">
                        {{ \Filament\Support\generate_icon_html($field->getPauseIcon(), size: IconSize::ExtraSmall) }}
                    </span>
                </button>

                <div
                    class="fff-audio-field__waveform"
                    x-ref="waveform"
                    x-on:pointerdown="onWaveformPointerDown($event)"
                    x-bind:class="{ 'is-disabled': ! canInteract }"
                    role="slider"
                    x-bind:aria-valuenow="Math.round(progressRatio * 100)"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="{{ __('filament-flex-fields::default.audio.progress') }}"
                >
                    <div class="fff-audio-field__waveform-skeleton" aria-hidden="true">
                        @for ($skeletonIndex = 0; $skeletonIndex < 40; $skeletonIndex++)
                            <span
                                class="fff-audio-field__skeleton-bar"
                                style="--fff-audio-skeleton-i: {{ $skeletonIndex }}"
                            ></span>
                        @endfor
                        <span class="fff-audio-field__waveform-skeleton-shimmer"></span>
                    </div>

                    <template x-for="(peak, index) in displayWaveform" :key="index">
                        <span
                            class="fff-audio-field__bar"
                            x-bind:style="'height: ' + peak + '%; --fff-audio-bar-i: ' + index"
                            x-bind:class="{ 'is-played': barIsPlayed(index) }"
                        ></span>
                    </template>
                </div>

                <span class="fff-audio-field__time" x-text="timeLabel">0:00</span>
            </div>

            @if (filled($audioSrc))
                <audio
                    x-ref="audio"
                    class="fff-audio-field__audio"
                    src="{{ e($audioSrc) }}"
                    x-bind:src="audioSrc"
                    preload="metadata"
                    @if ($shouldLoop()) loop @endif
                ></audio>
            @endif
        </div>
    </div>
</x-dynamic-component>
