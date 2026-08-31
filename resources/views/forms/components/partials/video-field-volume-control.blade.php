@php
    use Filament\Support\Enums\IconSize;
@endphp

<div
    class="fff-video-field__volume"
    x-on:mouseenter="openVolumePopoverHover()"
    x-on:mouseleave="closeVolumePopoverHover()"
    x-on:click.outside="closeVolumePanel()"
>
    <button
        type="button"
        class="fff-video-field__glass-btn fff-video-field__glass-btn--volume"
        x-ref="volumeTrigger"
        x-on:click="toggleVolumeControl()"
        x-bind:aria-label="muted || volume === 0 ? @js(__('filament-flex-fields::default.video.unmute')) : @js(__('filament-flex-fields::default.video.mute'))"
        x-bind:aria-expanded="volumeOpen ? 'true' : 'false'"
        x-bind:data-muted="muted || volume === 0 ? '' : null"
        x-bind:data-volume-level="volumeLevel"
    >
        <span x-show="! muted && volume > 0" aria-hidden="true">
            {{ \Filament\Support\generate_icon_html($field->getVolumeIcon(), size: $iconSize) }}
        </span>
        <span x-show="muted || volume === 0" style="display: none" aria-hidden="true">
            {{ \Filament\Support\generate_icon_html($field->getMuteIcon(), size: $iconSize) }}
        </span>
    </button>

    <div
        class="fff-video-field__volume-popover"
        x-ref="volumePopover"
        x-show="volumeOpen"
        x-transition.opacity.duration.200ms
        style="display: none"
        role="group"
        aria-label="{{ __('filament-flex-fields::default.video.volume') }}"
    >
        <div
            class="fff-video-field__volume-slider"
            x-ref="volumeSlider"
            role="slider"
            x-bind:aria-valuemin="0"
            x-bind:aria-valuemax="100"
            x-bind:aria-valuenow="Math.round(volume * 100)"
            x-bind:aria-valuetext="Math.round(volume * 100) + '%'"
            x-bind:aria-disabled="! canInteract ? 'true' : 'false'"
            x-bind:class="{ 'is-dragging': volumeDragging }"
            x-bind:style="volumeSliderCssVars"
            x-on:pointerdown="onVolumePointerDown($event)"
            x-on:pointermove="onVolumePointerMove($event)"
            x-on:pointerup="onVolumePointerUp($event)"
            x-on:pointerleave="onVolumePointerLeave()"
            x-on:lostpointercapture="onVolumeLostPointerCapture($event)"
            x-on:wheel.prevent="onVolumeWheel($event)"
        >
            <div class="fff-video-field__volume-track" aria-hidden="true">
                <div class="fff-video-field__volume-fill"></div>
            </div>
            <div class="fff-video-field__volume-thumb" aria-hidden="true"></div>
        </div>
    </div>
</div>
