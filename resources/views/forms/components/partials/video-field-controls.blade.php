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
    <div class="fff-video-field__dock fff-video-field__dock--default">
        @if ($hasMetadata)
            <div class="fff-video-field__meta">
                @if (filled($subtitle))
                    <span class="fff-video-field__meta-kicker">{{ $subtitle }}</span>
                @endif
                @if (filled($title))
                    <span class="fff-video-field__meta-title">{{ $title }}</span>
                @endif
            </div>
        @endif

        <div class="fff-video-field__progress-block fff-video-field__progress-block--default">
            @include('filament-flex-fields::forms.components.partials.video-field-progress')
        </div>

        <div class="fff-video-field__toolbar fff-video-field__toolbar--default">
            <div class="fff-video-field__toolbar-start">
                <button
                    type="button"
                    class="fff-video-field__glass-btn"
                    x-on:click="togglePlay()"
                    x-bind:aria-label="playing ? @js(__('filament-flex-fields::default.video.pause')) : @js(__('filament-flex-fields::default.video.play'))"
                >
                    <span class="fff-video-field__icon-play" x-show="! playing" aria-hidden="true">
                        {{ \Filament\Support\generate_icon_html($field->getPlayIcon(), size: IconSize::Small) }}
                    </span>
                    <span class="fff-video-field__icon-pause" x-show="playing" style="display: none" aria-hidden="true">
                        {{ \Filament\Support\generate_icon_html($field->getPauseIcon(), size: IconSize::Small) }}
                    </span>
                </button>

                <div class="fff-video-field__duration-pill" aria-live="polite">
                    <span x-text="durationRangeLabel">0:00 / 0:00</span>
                </div>
            </div>

            <div class="fff-video-field__toolbar-end">
                @if ($hasVolumeControl())
                    @include('filament-flex-fields::forms.components.partials.video-field-volume-control', [
                        'iconSize' => IconSize::Small,
                    ])
                @endif

                @include('filament-flex-fields::forms.components.partials.video-field-toolbar-end', [
                    'toolbarIconSize' => IconSize::Small,
                ])
            </div>
        </div>
    </div>
</div>
