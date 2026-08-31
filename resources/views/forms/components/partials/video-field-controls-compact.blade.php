@php
    use Filament\Support\Enums\IconSize;
@endphp

<div
    class="fff-video-field__scrim is-panel-visible"
    x-bind:class="{ 'is-panel-visible': panelVisible }"
></div>

<div
    class="fff-video-field__ui is-panel-visible"
    x-bind:class="{ 'is-panel-visible': panelVisible }"
    x-bind:inert="! panelVisible"
>
    <div class="fff-video-field__dock fff-video-field__dock--compact">
        <div class="fff-video-field__toolbar fff-video-field__toolbar--compact">
            <div class="fff-video-field__toolbar-start fff-video-field__toolbar-start--compact">
                <button
                    type="button"
                    class="fff-video-field__glass-btn"
                    x-on:click="togglePlay()"
                    x-bind:aria-label="playing ? @js(__('filament-flex-fields::default.video.pause')) : @js(__('filament-flex-fields::default.video.play'))"
                >
                    <span class="fff-video-field__icon-play" x-show="! playing" aria-hidden="true">
                        {{ \Filament\Support\generate_icon_html($field->getPlayIcon(), size: IconSize::ExtraSmall) }}
                    </span>
                    <span class="fff-video-field__icon-pause" x-show="playing" style="display: none" aria-hidden="true">
                        {{ \Filament\Support\generate_icon_html($field->getPauseIcon(), size: IconSize::ExtraSmall) }}
                    </span>
                </button>

                @if ($hasVolumeControl())
                    @include('filament-flex-fields::forms.components.partials.video-field-volume-control', [
                        'iconSize' => IconSize::ExtraSmall,
                    ])
                @endif
            </div>

            <div class="fff-video-field__time-controls fff-video-field__time-controls--compact">
                <span class="fff-video-field__time-current" x-text="currentLabel" aria-live="off">0:00</span>

                <div class="fff-video-field__progress-block fff-video-field__progress-block--compact">
                    @include('filament-flex-fields::forms.components.partials.video-field-progress')
                </div>

                <span class="fff-video-field__time-remaining" x-text="remainingLabel" aria-live="polite">-0:00</span>
            </div>

            <div class="fff-video-field__toolbar-end fff-video-field__toolbar-end--compact">
                @include('filament-flex-fields::forms.components.partials.video-field-toolbar-end', [
                    'toolbarIconSize' => IconSize::ExtraSmall,
                ])
            </div>
        </div>
    </div>
</div>
