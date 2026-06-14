@php
    use Filament\Support\Enums\Alignment;

    $fieldWrapperView = $getFieldWrapperView();
    $id = $getId();
    $automaticallyCropImagesAspectRatio = $getAutomaticallyCropImagesAspectRatio();
    $automaticallyResizeImagesHeight = $getAutomaticallyResizeImagesHeight();
    $automaticallyResizeImagesWidth = $getAutomaticallyResizeImagesWidth();
    $isAvatar = $isAvatar();
    $isMultiple = $isMultiple();
    $key = $getKey();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $hasImageEditor = $hasImageEditor();
    $isImageEditorExplicitlyEnabled = $isImageEditorExplicitlyEnabled();
    $hasCircleCropper = $hasCircleCropper();
    $livewireKey = $getLivewireKey();
    $flexConfig = $field->getFlexFileUploadAlpineConfiguration();
    $placeholder = $field->getEffectivePlaceholder();

    $alignment = $getAlignment() ?? Alignment::Start;

    if (! $alignment instanceof Alignment) {
        $alignment = filled($alignment) ? (Alignment::tryFrom($alignment) ?? $alignment) : null;
    }

    $initialSummaryLabel = $flexConfig['showUploadSummary']
        ? __('filament-flex-fields::default.file_upload.summary', ['count' => 0, 'size' => '0'])
        : null;
    $hasUploadMeta = filled($initialSummaryLabel) || filled($flexConfig['remainingSlotsLabel']);
    $hasDualUploadMeta = filled($initialSummaryLabel) && filled($flexConfig['remainingSlotsLabel']);
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    label-tag="div"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'flex-file-upload'])
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flex-file-upload', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="flexFileUploadFormComponent({
            showUploadSummary: @js($flexConfig['showUploadSummary']),
            requireReplaceConfirmation: @js($flexConfig['requireReplaceConfirmation']),
            replaceConfirmationMessage: @js($flexConfig['replaceConfirmationMessage']),
            summaryTemplate: @js($flexConfig['summaryTemplate']),
            remainingSlotsLabel: @js($flexConfig['remainingSlotsLabel']),
            showFileIcon: @js($flexConfig['showFileIcon']),
            isMultiple: @js($isMultiple),
            initialSummaryLabel: @js($initialSummaryLabel ?? ''),
        })"
        x-init="init()"
        x-bind:class="{ 'is-ready': displayReady }"
        @class([
            ...$field->getWrapperClasses(),
            'fff-flex-file-upload__shell',
            'fff-flex-file-upload--has-meta' => $hasUploadMeta,
            'fff-flex-file-upload--has-dual-meta' => $hasDualUploadMeta,
        ])
    >
        <div class="fff-flex-file-upload__stage">
            @include('filament-flex-fields::forms.components.partials.flex-file-upload-skeleton')

            <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('file-upload', 'filament/forms') }}"
            x-data="fileUploadFormComponent({
                        acceptedFileTypes: @js($getAcceptedFileTypes()),
                        automaticallyCropImagesAspectRatio: @js($automaticallyCropImagesAspectRatio),
                        automaticallyOpenImageEditorForAspectRatio: @js($getAutomaticallyOpenImageEditorForAspectRatio()),
                        automaticallyResizeImagesMode: @js($getAutomaticallyResizeImagesMode()),
                        automaticallyResizeImagesHeight: @js($automaticallyResizeImagesHeight),
                        automaticallyResizeImagesWidth: @js($automaticallyResizeImagesWidth),
                        cancelUploadUsing: (fileKey) => {
                            $wire.cancelUpload(`{{ $statePath }}.${fileKey}`)
                        },
                        canEditSvgs: @js($canEditSvgs()),
                        confirmSvgEditingMessage: @js(__('filament-forms::components.file_upload.editor.svg.messages.confirmation')),
                        deleteUploadedFileUsing: async (fileKey) => {
                            return await $wire.callSchemaComponentMethod(
                                @js($key),
                                'deleteUploadedFile',
                                { fileKey },
                            )
                        },
                        disabledSvgEditingMessage: @js(__('filament-forms::components.file_upload.editor.svg.messages.disabled')),
                        getUploadedFilesUsing: async () => {
                            return await Livewire.fireAction(
                                $wire.__instance,
                                'callSchemaComponentMethod',
                                [@js($key), 'getUploadedFiles'],
                                { async: true },
                            )
                        },
                        hasCircleCropper: @js($hasCircleCropper),
                        hasImageEditor: @js($hasImageEditor),
                        imageEditorEmptyFillColor: @js($getImageEditorEmptyFillColor()),
                        imageEditorMode: @js($getImageEditorMode()),
                        imageEditorViewportHeight: @js($getImageEditorViewportHeight()),
                        imageEditorViewportWidth: @js($getImageEditorViewportWidth()),
                        imagePreviewHeight: @js($getImagePreviewHeight()),
                        isAvatar: @js($isAvatar),
                        isDeletable: @js($isDeletable()),
                        isDisabled: @js($isDisabled),
                        isDownloadable: @js($isDownloadable()),
                        isImageEditorExplicitlyEnabled: @js($isImageEditorExplicitlyEnabled),
                        isMultiple: @js($isMultiple),
                        isOpenable: @js($isOpenable()),
                        isPasteable: @js($isPasteable()),
                        isPreviewable: @js($isPreviewable()),
                        isReorderable: @js($isReorderable()),
                        isSvgEditingConfirmed: @js($isSvgEditingConfirmed()),
                        itemPanelAspectRatio: @js($getItemPanelAspectRatio()),
                        loadingIndicatorPosition: @js($getLoadingIndicatorPosition()),
                        locale: @js(app()->getLocale()),
                        maxFiles: @js($maxFiles = $getMaxFiles()),
                        maxFilesValidationMessage: @js($maxFiles ? trans_choice('validation.max.array', $maxFiles, ['attribute' => $getValidationAttribute(), 'max' => $maxFiles]) : null),
                        maxParallelUploads: @js($getMaxParallelUploads()),
                        maxSize: @js(($size = $getMaxSize()) ? "{$size}KB" : null),
                        mimeTypeMap: @js($getMimeTypeMap()),
                        minSize: @js(($size = $getMinSize()) ? "{$size}KB" : null),
                        panelAspectRatio: @js($getPanelAspectRatio()),
                        panelLayout: @js($getPanelLayout()),
                        placeholder: @js($placeholder),
                        removeUploadedFileButtonPosition: @js($getRemoveUploadedFileButtonPosition()),
                        removeUploadedFileUsing: async (fileKey) => {
                            return await $wire.callSchemaComponentMethod(
                                @js($key),
                                'removeUploadedFile',
                                { fileKey },
                            )
                        },
                        reorderUploadedFilesUsing: async (fileKeys) => {
                            return await $wire.callSchemaComponentMethod(
                                @js($key),
                                'reorderUploadedFiles',
                                { fileKeys },
                            )
                        },
                        shouldAppendFiles: @js($shouldAppendFiles()),
                        shouldAutomaticallyUpscaleImagesWhenResizing: @js($shouldAutomaticallyUpscaleImagesWhenResizing()),
                        shouldOrientImageFromExif: @js($shouldOrientImagesFromExif()),
                        shouldTransformImage: @js($automaticallyCropImagesAspectRatio || $automaticallyResizeImagesHeight || $automaticallyResizeImagesWidth),
                        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                        uploadButtonPosition: @js($getUploadButtonPosition()),
                        uploadingMessage: @js($getUploadingMessage()),
                        uploadProgressIndicatorPosition: @js($getUploadProgressIndicatorPosition()),
                        uploadUsing: (fileKey, file, success, error, progress) => {
                            $wire.upload(
                                `{{ $statePath }}.${fileKey}`,
                                file,
                                () => {
                                    success(fileKey)
                                },
                                error,
                                (progressEvent) => {
                                    progress(true, progressEvent.detail.progress, 100)
                                },
                            )
                        },
                    })"
            wire:ignore
            wire:key="{{ $livewireKey }}.upload.{{
                substr(md5(serialize([
                    $isDisabled,
                ])), 0, 64)
            }}"
            {{
                $attributes
                    ->merge([
                        'aria-labelledby' => "{$id}-label",
                        'id' => $id,
                        'role' => 'group',
                    ], escape: false)
                    ->merge($getExtraAlpineAttributes(), escape: false)
                    ->class([
                        'fff-flex-file-upload__live',
                        'fi-fo-file-upload',
                        'fi-fo-file-upload-avatar' => $isAvatar,
                        ($alignment instanceof Alignment) ? "fi-align-{$alignment->value}" : $alignment,
                    ])
            }}
        >
            <div class="fi-fo-file-upload-input-ctn">
                <input
                    x-ref="input"
                    {{
                        $getExtraInputAttributeBag()
                            ->merge([
                                'aria-labelledby' => "{$id}-label",
                                'disabled' => $isDisabled,
                                'multiple' => $isMultiple,
                                'type' => 'file',
                            ], escape: false)
                    }}
                />
            </div>

            <div
                x-show="error"
                x-text="error"
                x-cloak
                class="fi-fo-file-upload-error-message"
            ></div>

            @if ($hasImageEditor && (! $isDisabled))
                @include('filament-flex-fields::forms.components.partials.flex-file-upload-image-editor')
            @endif
        </div>
        </div>

        @if ($hasUploadMeta)
            <div @class(['fff-flex-file-upload__meta', 'fff-flex-file-upload__meta--dual' => $hasDualUploadMeta])>
                @if ($flexConfig['showUploadSummary'])
                    <div
                        x-show="showUploadSummary && summaryLabel"
                        x-cloak
                        class="fff-flex-file-upload__summary"
                    >
                        <span x-text="summaryLabel">{{ $initialSummaryLabel }}</span>
                    </div>
                @endif

                @if (filled($flexConfig['remainingSlotsLabel']))
                    <div
                        x-show="remainingSlotsLabel"
                        x-cloak
                        class="fff-flex-file-upload__remaining-slots"
                    >
                        <span x-text="remainingSlotsLabel">{{ $flexConfig['remainingSlotsLabel'] }}</span>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-dynamic-component>
