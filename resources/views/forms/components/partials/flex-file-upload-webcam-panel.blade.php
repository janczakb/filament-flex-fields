@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;

    $webcamModalId = 'fff-webcam-upload-'.$id;
@endphp

<div
    id="{{ $id }}-source-webcam"
    role="tabpanel"
    aria-labelledby="{{ $id }}-source-webcam-trigger"
    class="fff-flex-file-upload__source-panel fff-flex-file-upload__source-panel--webcam"
    x-bind:class="{ 'is-active': isUploadSource('webcam') }"
    x-bind:aria-hidden="! isUploadSource('webcam')"
>
    <button
        type="button"
        class="fff-flex-file-upload__webcam-placeholder fff-flex-file-upload__source-dropzone"
        x-cloak
        x-on:click="openWebcamModal()"
        x-bind:disabled="isDisabled"
        x-bind:aria-label="`${labels.webcamOpen} ${labels.webcamOpenAction}`"
    >
        <span class="fff-flex-file-upload__webcam-placeholder-label">
            <span class="fff-flex-file-upload__source-dropzone-icon fff-flex-file-upload__webcam-placeholder-icon" aria-hidden="true">
                <x-filament::icon :icon="GravityIcon::Camera" class="fff-flex-file-upload__source-dropzone-icon-svg fff-flex-file-upload__webcam-placeholder-icon-svg" />
            </span>

            <span class="fff-flex-file-upload__source-dropzone-text fff-flex-file-upload__webcam-placeholder-text">
                <span class="fff-flex-file-upload__source-dropzone-copy" x-text="labels.webcamOpen">
                    {{ __('filament-flex-fields::default.file_upload.sources.webcam_open') }}
                </span>
                <span class="fff-flex-file-upload__source-dropzone-action fff-flex-file-upload__webcam-placeholder-action" x-text="labels.webcamOpenAction">
                    {{ __('filament-flex-fields::default.file_upload.sources.webcam_open_action') }}
                </span>
            </span>
        </span>
    </button>

    @include('filament-flex-fields::forms.components.partials.flex-file-upload-webcam-modal', [
        'webcamModalId' => $webcamModalId,
    ])
</div>
