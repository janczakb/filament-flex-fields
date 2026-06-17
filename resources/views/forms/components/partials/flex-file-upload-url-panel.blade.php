@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
@endphp

<div
    id="{{ $id }}-source-url"
    role="tabpanel"
    aria-labelledby="{{ $id }}-source-url-trigger"
    class="fff-flex-file-upload__source-panel fff-flex-file-upload__source-panel--url"
    x-bind:class="{ 'is-active': isUploadSource('url') }"
    x-bind:aria-hidden="! isUploadSource('url')"
>
    <div @class(['fff-flex-file-upload__source-dropzone', 'fff-flex-file-upload__url-dropzone', 'is-disabled' => $isDisabled])>
        <div class="fff-flex-file-upload__url-dropzone-content">
            <span class="fff-flex-file-upload__source-dropzone-icon" aria-hidden="true">
                <x-filament::icon
                    :icon="GravityIcon::Globe"
                    class="fff-flex-file-upload__source-dropzone-icon-svg"
                />
            </span>

            <div class="fff-flex-file-upload__url-dropzone-controls">
                <span class="fff-flex-file-upload__source-dropzone-copy" x-text="labels.urlOpen">
                    {{ __('filament-flex-fields::default.file_upload.sources.url_open') }}
                </span>

                <x-filament::input.wrapper
                    :attributes="
                        \Filament\Support\prepare_inherited_attributes(new \Illuminate\View\ComponentAttributeBag())
                            ->class(['fff-flex-file-upload__url-input'])
                    "
                >
                    <x-filament::input
                        type="url"
                        x-model="urlValue"
                        x-bind:disabled="urlImporting || isDisabled"
                        placeholder="{{ __('filament-flex-fields::default.file_upload.sources.url_placeholder') }}"
                        x-on:keydown.enter.prevent="importFromUrl()"
                    />
                </x-filament::input.wrapper>

                <button
                    type="button"
                    class="fff-flex-file-upload__source-dropzone-action"
                    x-on:click="importFromUrl()"
                    x-bind:disabled="urlImporting || isDisabled"
                >
                    <span x-show="! urlImporting" x-text="labels.urlImport">{{ __('filament-flex-fields::default.file_upload.sources.url_import') }}</span>
                    <span x-show="urlImporting" x-cloak x-text="labels.urlImporting">{{ __('filament-flex-fields::default.file_upload.sources.url_importing') }}</span>
                </button>
            </div>
        </div>
    </div>

    <p
        x-show="$data.urlError"
        x-cloak
        x-text="$data.urlError"
        class="fff-flex-file-upload__source-error"
        role="alert"
    ></p>
</div>
