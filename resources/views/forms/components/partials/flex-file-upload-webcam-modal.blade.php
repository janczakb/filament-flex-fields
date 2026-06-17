@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
@endphp

<x-filament::modal
    :id="$webcamModalId"
    :heading="__('filament-flex-fields::default.file_upload.sources.webcam_modal_heading')"
    :description="__('filament-flex-fields::default.file_upload.sources.webcam_modal_description')"
    :icon="GravityIcon::Camera"
    icon-color="primary"
    width="3xl"
    teleport="body"
    :close-button="true"
    :close-by-clicking-away="false"
    :close-by-escaping="true"
    class="fff-flex-file-upload-webcam-modal"
    :extra-modal-window-attribute-bag="(new \Illuminate\View\ComponentAttributeBag())->class(['fff-flex-file-upload-webcam-modal__window'])"
>
    <div
        class="fff-flex-file-upload-webcam-modal__stage"
        x-bind:class="{
            'is-review': webcamCaptureStep === 'review',
            'is-ready': webcamReady,
        }"
    >
        <div
            class="fff-flex-file-upload__webcam-stage fff-flex-file-upload__webcam-stage--modal"
            x-show="webcamCaptureStep === 'camera'"
            x-cloak
        >
            <video
                id="{{ $webcamModalId }}-video"
                x-ref="webcamVideo"
                class="fff-flex-file-upload__webcam-video"
                playsinline
                webkit-playsinline
                muted
                autoplay
            ></video>
            <canvas x-ref="webcamCanvas" class="sr-only" aria-hidden="true"></canvas>

            <div
                x-show="webcamStarting"
                x-cloak
                class="fff-flex-file-upload__webcam-overlay"
            >
                <span class="fff-flex-file-upload__webcam-spinner" aria-hidden="true"></span>
                <span x-text="labels.webcamStarting">{{ __('filament-flex-fields::default.file_upload.sources.webcam_starting') }}</span>
            </div>

            <div
                x-show="webcamReady && ! webcamStarting"
                x-cloak
                class="fff-flex-file-upload__webcam-controls"
            >
                <div
                    class="fff-flex-file-upload__webcam-dock"
                    x-bind:class="{
                        'fff-flex-file-upload__webcam-dock--flip': webcamCanFlip,
                        'fff-flex-file-upload__webcam-dock--flash': webcamCanUseFlash,
                        'fff-flex-file-upload__webcam-dock--solo': ! webcamCanFlip && ! webcamCanUseFlash,
                    }"
                >
                    <button
                        type="button"
                        x-show="webcamCanFlip"
                        x-cloak
                        class="fff-flex-file-upload__webcam-control fff-flex-file-upload__webcam-control--flip"
                        x-on:click="flipWebcam()"
                        x-bind:disabled="webcamStarting || isDisabled"
                        x-bind:aria-label="labels.webcamFlipCamera"
                    >
                        <x-filament::icon
                            :icon="GravityIcon::ArrowsRotateRight"
                            class="fff-flex-file-upload__webcam-control-icon"
                        />
                    </button>

                    <button
                        type="button"
                        class="fff-flex-file-upload__webcam-control fff-flex-file-upload__webcam-control--shutter"
                        x-on:click="captureWebcamPhoto()"
                        x-bind:disabled="! webcamReady || isDisabled"
                        x-bind:aria-label="labels.webcamCapture"
                    >
                        <span class="fff-flex-file-upload__webcam-shutter-ring" aria-hidden="true"></span>
                        <span class="fff-flex-file-upload__webcam-shutter-core">
                            <x-filament::icon
                                :icon="GravityIcon::Camera"
                                class="fff-flex-file-upload__webcam-shutter-icon"
                            />
                        </span>
                    </button>

                    <button
                        type="button"
                        x-show="webcamCanUseFlash"
                        x-cloak
                        class="fff-flex-file-upload__webcam-control fff-flex-file-upload__webcam-control--flash"
                        x-bind:class="{ 'is-active': webcamFlashEnabled }"
                        x-on:click="toggleWebcamFlash()"
                        x-bind:disabled="! webcamReady || isDisabled"
                        x-bind:aria-label="webcamFlashEnabled ? labels.webcamFlashOff : labels.webcamFlashOn"
                        x-bind:aria-pressed="webcamFlashEnabled"
                    >
                        <x-filament::icon
                            :icon="GravityIcon::ThunderboltFill"
                            class="fff-flex-file-upload__webcam-control-icon"
                        />
                    </button>
                </div>
            </div>
        </div>

        <div
            class="fff-flex-file-upload__webcam-review"
            x-show="webcamCaptureStep === 'review'"
            x-cloak
        >
            <img
                x-bind:src="webcamPendingPreviewUrl"
                alt=""
                class="fff-flex-file-upload__webcam-review-image"
            />

            <div class="fff-flex-file-upload__webcam-review-actions">
                <x-filament::button
                    type="button"
                    color="gray"
                    x-on:click="retakeWebcamPhoto()"
                >
                    <span x-text="labels.webcamRetake">{{ __('filament-flex-fields::default.file_upload.sources.webcam_retake') }}</span>
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="primary"
                    x-on:click="confirmWebcamPhoto()"
                    x-bind:disabled="webcamConfirming"
                >
                    <span x-show="! webcamConfirming" x-text="labels.webcamConfirm">{{ __('filament-flex-fields::default.file_upload.sources.webcam_confirm') }}</span>
                    <span x-show="webcamConfirming" x-cloak x-text="labels.webcamConfirming">{{ __('filament-flex-fields::default.file_upload.sources.webcam_confirming') }}</span>
                </x-filament::button>
            </div>
        </div>

        <p
            x-show="webcamError"
            x-cloak
            x-text="webcamError"
            class="fff-flex-file-upload__source-error fff-flex-file-upload__webcam-modal-error"
            role="alert"
        ></p>
    </div>
</x-filament::modal>
