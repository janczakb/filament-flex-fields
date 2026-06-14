@php
    use Filament\Support\Enums\IconSize;
@endphp

<div
    class="fff-video-field__scrim is-panel-visible"
    x-bind:class="{ 'is-panel-visible': showUi || ! playing }"
></div>

<div
    class="fff-video-field__ui is-panel-visible"
    x-bind:class="{ 'is-panel-visible': showUi || ! playing }"
>
    <div class="fff-video-field__dock fff-video-field__dock--compact">
        <div class="fff-video-field__progress-block fff-video-field__progress-block--compact">
            <div
                class="fff-video-field__progress-wrap"
                x-bind:class="{ 'is-disabled': ! canScrub }"
            >
                <div class="fff-video-field__progress-track"></div>
                <div class="fff-video-field__progress-buffer" x-bind:style="'width: ' + (bufferedRatio * 100) + '%'"></div>
                <div class="fff-video-field__progress-played" x-bind:style="'width: ' + (displayProgressRatio * 100) + '%'"></div>
                <div
                    class="fff-video-field__progress-thumb"
                    x-bind:style="'left: calc(' + (displayProgressRatio * 100) + '%)'"
                    aria-hidden="true"
                ></div>
                <input
                    type="range"
                    class="fff-video-field__progress-input"
                    min="0"
                    max="1000"
                    step="1"
                    x-bind:value="progressInputValue"
                    x-bind:disabled="! canScrub"
                    x-on:input="onScrubInput($event)"
                    x-on:change="onScrubChange($event)"
                    aria-label="{{ __('filament-flex-fields::default.video.progress') }}"
                />
            </div>
        </div>

        <div class="fff-video-field__toolbar fff-video-field__toolbar--compact">
            <div class="fff-video-field__toolbar-start">
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
                    <div class="fff-video-field__volume fff-video-field__volume--expand">
                        <button
                            type="button"
                            class="fff-video-field__glass-btn fff-video-field__glass-btn--volume"
                            x-on:click="toggleMute()"
                            x-bind:aria-label="muted ? @js(__('filament-flex-fields::default.video.unmute')) : @js(__('filament-flex-fields::default.video.mute'))"
                        >
                            <span x-show="! muted" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getVolumeIcon(), size: IconSize::ExtraSmall) }}
                            </span>
                            <span x-show="muted" style="display: none" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getMuteIcon(), size: IconSize::ExtraSmall) }}
                            </span>
                        </button>
                        <div class="fff-video-field__volume-track">
                            <input
                                type="range"
                                class="fff-video-field__volume-input"
                                min="0"
                                max="100"
                                x-bind:value="Math.round(volume * 100)"
                                x-bind:style="'--fff-video-volume-fill: ' + Math.round(volume * 100) + '%'"
                                x-on:input="onVolumeInput($event)"
                                aria-label="{{ __('filament-flex-fields::default.video.volume') }}"
                            />
                        </div>
                    </div>
                @endif

                <div class="fff-video-field__duration-pill" aria-live="polite">
                    <span x-text="durationRangeLabel">0:00 / 0:00</span>
                </div>
            </div>

            @if ($showPictureInPictureControl || $isFullscreenable())
                <div class="fff-video-field__toolbar-end">
                    @if ($showPictureInPictureControl)
                        <button
                            type="button"
                            class="fff-video-field__glass-btn"
                            x-on:click="togglePictureInPicture()"
                            x-bind:disabled="! pictureInPictureSupported"
                            x-bind:aria-label="isPictureInPicture ? @js(__('filament-flex-fields::default.video.exit_picture_in_picture')) : @js(__('filament-flex-fields::default.video.picture_in_picture'))"
                        >
                            <span x-show="! isPictureInPicture" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getPictureInPictureIcon(), size: IconSize::ExtraSmall) }}
                            </span>
                            <span x-show="isPictureInPicture" style="display: none" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getExitPictureInPictureIcon(), size: IconSize::ExtraSmall) }}
                            </span>
                        </button>
                    @endif

                    @if ($isFullscreenable())
                        <button
                            type="button"
                            class="fff-video-field__glass-btn"
                            x-on:click="toggleFullscreen()"
                            x-bind:aria-label="isFullscreen ? @js(__('filament-flex-fields::default.video.exit_fullscreen')) : @js(__('filament-flex-fields::default.video.fullscreen'))"
                        >
                            <span x-show="! isFullscreen" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getFullscreenIcon(), size: IconSize::ExtraSmall) }}
                            </span>
                            <span x-show="isFullscreen" style="display: none" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getExitFullscreenIcon(), size: IconSize::ExtraSmall) }}
                            </span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
