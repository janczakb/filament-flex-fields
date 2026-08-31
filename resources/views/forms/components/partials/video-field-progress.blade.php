<div
    class="fff-video-field__progress-wrap"
    x-ref="progressWrap"
    role="slider"
    x-bind:aria-valuemin="0"
    x-bind:aria-valuemax="1000"
    x-bind:aria-valuenow="progressInputValue"
    x-bind:aria-valuetext="previewTimeLabel"
    x-bind:aria-disabled="! canScrub ? 'true' : 'false'"
    x-bind:class="{
        'is-disabled': ! canScrub,
        'is-pointing': progressPointing,
        'is-dragging': progressDragging,
    }"
    x-bind:style="progressSliderCssVars"
    x-on:pointerdown="onProgressPointerDown($event)"
    x-on:pointermove="onProgressPointerMove($event)"
    x-on:pointerup="onProgressPointerUp($event)"
    x-on:pointerleave="onProgressPointerLeave()"
    x-on:lostpointercapture="onProgressLostPointerCapture($event)"
>
    <div class="fff-video-field__progress-track">
        <div class="fff-video-field__progress-buffer" aria-hidden="true"></div>
        <div class="fff-video-field__progress-played" aria-hidden="true"></div>
    </div>

    <div class="fff-video-field__progress-thumb" aria-hidden="true"></div>

    <div class="fff-video-field__progress-preview" aria-hidden="true">
        <div class="fff-video-field__progress-preview-dot" aria-hidden="true"></div>
    </div>

    <div
        class="fff-video-field__progress-thumbnail"
        x-ref="progressThumbnail"
        x-bind:style="progressThumbnailStyle"
        aria-hidden="true"
    >
        <div class="fff-video-field__progress-thumbnail-media">
            <canvas
                x-ref="previewCanvas"
                class="fff-video-field__progress-preview-canvas"
                x-show="previewFrameReady"
            ></canvas>
            <span class="fff-video-field__progress-preview-time" x-text="previewTimeLabel"></span>
        </div>
    </div>
</div>
