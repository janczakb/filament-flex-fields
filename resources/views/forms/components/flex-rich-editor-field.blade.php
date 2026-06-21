@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Icons\Heroicon;
    use Filament\Forms\View\FormsIconAlias;

    $extraAttributeBag = $getExtraAttributeBag();
    $fieldWrapperView = $getFieldWrapperView();
    $isDisabled = $isDisabled();
    $label = $getLabel();
    $livewireKey = $getLivewireKey();
    $key = $getKey();
    $statePath = $getStatePath();
    $mentions = $getMentionsForJs();
    $toolbarButtons = $getToolbarButtons();
    $tools = $getTools();
    $floatingToolbars = $getFloatingToolbars();
    $linkProtocols = $getLinkProtocols();
    $fileAttachmentsMaxSize = $getFileAttachmentsMaxSize();
    $fileAttachmentsAcceptedFileTypes = $getFileAttachmentsAcceptedFileTypes();
    $wrapperClasses = $field->getWrapperClasses();
    $shouldShowFooter = $field->shouldShowRichEditorFooter();
    $shouldShowJsonBadge = $field->shouldShowJsonBadge();
    $jsonBadgeLabel = $field->getJsonBadgeLabel();
    $footerConfig = $field->getRichEditorFooterConfigForJs();
    $distractionFreeHiddenTools = $field->getDistractionFreeHiddenToolsForJs();
    $shouldEnableDistractionFree = $field->shouldEnableRichEditorDistractionFree();
    $editorSize = $field->getSize();
    $editorVariant = $field->getVariant();
    $isInvalid = $errors->has($statePath);
    $youtubeConfig = $field->getYoutubeConfigForJs();
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($extraAttributeBag)
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'rich-editor-field'])
    @include('filament-flex-fields::partials.flex-rich-editor-runtime-preload')

    <div
        @class([
            'fff-rich-editor',
            'fi-fo-rich-editor',
            'fff-rich-editor--' . $editorSize,
            'fff-rich-editor--' . $editorVariant,
            'is-disabled' => $isDisabled,
            'is-invalid' => $isInvalid,
        ])
    >
        <div
            x-data="{ shouldMountRichEditor: @js($isDisabled) }"
            x-intersect:enter.once.margin.300px="shouldMountRichEditor = true"
        >
            <template x-if="shouldMountRichEditor">
                <div
                    x-load
                    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flex-rich-editor', FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
                    x-data="flexRichEditorFormComponent({
                richEditorSrc: @js(\Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('rich-editor', 'filament/forms')),
                acceptedFileTypes: @js($fileAttachmentsAcceptedFileTypes),
                acceptedFileTypesValidationMessage: @js($fileAttachmentsAcceptedFileTypes ? __('filament-forms::components.rich_editor.file_attachments_accepted_file_types_message', ['values' => implode(', ', $fileAttachmentsAcceptedFileTypes)]) : null),
                activePanel: @js($getActivePanel()),
                canAttachFiles: @js($hasFileAttachments()),
                deleteCustomBlockButtonIconHtml: @js(\Filament\Support\generate_icon_html(Heroicon::Trash, alias: FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCK_DELETE_BUTTON)->toHtml()),
                editCustomBlockButtonIconHtml: @js(\Filament\Support\generate_icon_html(Heroicon::PencilSquare, alias: FormsIconAlias::COMPONENTS_RICH_EDITOR_PANELS_CUSTOM_BLOCK_EDIT_BUTTON)->toHtml()),
                extensions: @js($getTipTapJsExtensions()),
                floatingToolbars: @js($floatingToolbars),
                getMentionLabelsUsing: async (mentions) => {
                    return await $wire.callSchemaComponentMethod(
                        @js($key),
                        'getMentionLabelsForJs',
                        { mentions },
                    )
                },
                getMentionSearchResultsUsing: async (query, char) => {
                    return await $wire.callSchemaComponentMethod(
                        @js($key),
                        'getMentionSearchResultsForJs',
                        { search: query, char },
                    )
                },
                hasResizableImages: @js($hasResizableImages()),
                isDisabled: @js($isDisabled),
                label: @js($label),
                isLiveDebounced: @js($isLiveDebounced()),
                isLiveOnBlur: @js($isLiveOnBlur()),
                key: @js($key),
                linkProtocols: @js($linkProtocols),
                liveDebounce: @js($getNormalizedLiveDebounce()),
                livewireId: @js($this->getId()),
                maxFileSize: @js($fileAttachmentsMaxSize),
                maxFileSizeValidationMessage: @js($fileAttachmentsMaxSize ? trans_choice('filament-forms::components.rich_editor.file_attachments_max_size_message', $fileAttachmentsMaxSize, ['max' => $fileAttachmentsMaxSize]) : null),
                mentions: @js($mentions),
                mergeTags: @js($getMergeTags()),
                noMergeTagSearchResultsMessage: @js($getNoMergeTagSearchResultsMessage()),
                placeholder: @js($getPlaceholder()),
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) }},
                statePath: @js($statePath),
                textColors: @js($getTextColorsForJs()),
                uploadingFileMessage: @js($getUploadingFileMessage()),
                footerConfig: @js($footerConfig),
                distractionFreeHiddenTools: @js($distractionFreeHiddenTools),
                pasteCleanupMode: @js($field->getPasteCleanupMode()),
                youtubeConfig: @js($youtubeConfig),
            })"
            x-bind:class="{
                'fff-rich-editor__root--uploading': isUploadingFile,
            }"
            x-effect="$el.closest('.fff-rich-editor').classList.toggle('fff-rich-editor--fullscreen', isRichEditorFullscreen)"
            wire:ignore
            wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $editorSize, $editorVariant])), 0, 64) }}"
            class="fff-rich-editor__root"
        >
            @if ((! $isDisabled) && filled($toolbarButtons))
                <div
                    class="fff-rich-editor__toolbar"
                    role="toolbar"
                    aria-label="{{ $label }}"
                    aria-orientation="horizontal"
                    x-ref="toolbar"
                    x-on:click.capture="
                        const trigger = $event.target.closest('.fi-fo-rich-editor-dropdown-tool-trigger');
                        if (! trigger) return;
                        const active = trigger.closest('.fi-fo-rich-editor-dropdown-tool');
                        $el.querySelectorAll('.fi-fo-rich-editor-dropdown-tool').forEach((group) => {
                            if (group === active) return;
                            const state = Alpine.$data(group);
                            if (state) state.open = false;
                        });
                    "
                >
                    @foreach ($toolbarButtons as $buttonGroup)
                        @if (! $loop->first)
                            <div
                                class="fff-rich-editor__toolbar-separator"
                                role="separator"
                                aria-orientation="vertical"
                            ></div>
                        @endif

                            <div
                                class="fff-rich-editor__toolbar-group"
                            >
                            @foreach ($buttonGroup as $button)
                                @if (is_string($button))
                                    @if ($shouldEnableDistractionFree)
                                        <div
                                            x-bind:class="{
                                                'fff-rich-editor__toolbar-button--distraction-free-hidden': isDistractionFreeToolbarButton(@js($button)),
                                            }"
                                        >
                                            {{ $tools[$button] ?? throw new LogicException("Toolbar button [{$button}] cannot be found.") }}
                                        </div>
                                    @else
                                        {{ $tools[$button] ?? throw new LogicException("Toolbar button [{$button}] cannot be found.") }}
                                    @endif
                                @else
                                    {{ $button }}
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif

            @foreach ($floatingToolbars as $nodeName => $buttons)
                <div
                    x-ref="floatingToolbar::{{ $nodeName }}"
                    class="fff-rich-editor__bubble fff-rich-editor__bubble-menu fff-rich-editor__bubble-menu--{{ $nodeName }} fi-not-prose"
                    role="toolbar"
                    aria-label="{{ $label }}"
                >
                    @foreach ($buttons as $button)
                        @if (is_string($button))
                            {{ $tools[$button] }}
                        @else
                            {{ $button }}
                        @endif
                    @endforeach
                </div>
            @endforeach

            <div
                x-show="isUploadingFile"
                x-cloak
                class="fff-rich-editor__uploading fi-fo-rich-editor-uploading-file-message"
            >
                {{ \Filament\Support\generate_loading_indicator_html() }}

                <span>{{ $getUploadingFileMessage() }}</span>
            </div>

            <div
                x-show="! isUploadingFile && fileValidationMessage"
                x-cloak
                class="fff-rich-editor__validation fi-fo-rich-editor-file-validation-message"
            >
                <span x-text="fileValidationMessage"></span>
            </div>

            <div {{ $getExtraInputAttributeBag()->class(['fff-rich-editor__main', 'fi-fo-rich-editor-main']) }}>
                <div
                    class="fff-rich-editor__content fi-fo-rich-editor-content fi-prose"
                    x-ref="editor"
                    role="textbox"
                    aria-multiline="true"
                    x-bind:aria-label="@js($label)"
                >
                </div>

                @if ($hasFileAttachments())
                    <div
                        x-ref="imageOverlay"
                        class="fff-rich-editor__image-overlay"
                        x-on:mousedown.stop
                    >
                        <div class="fff-rich-editor__image-overlay-actions">
                            <button
                                type="button"
                                class="fff-rich-editor__image-overlay-button"
                                aria-label="{{ __('filament-flex-fields::default.rich_editor.image.edit') }}"
                                x-on:mousedown.stop
                                x-on:click.stop="editSelectedImage()"
                            >
                                {!! \Filament\Support\generate_icon_html(GravityIcon::PencilToSquare, size: IconSize::Small)?->toHtml() !!}
                            </button>

                            <button
                                type="button"
                                class="fff-rich-editor__image-overlay-button fff-rich-editor__image-overlay-button--danger"
                                aria-label="{{ __('filament-flex-fields::default.rich_editor.image.delete') }}"
                                x-on:mousedown.stop
                                x-on:click.stop="deleteSelectedImage()"
                            >
                                {!! \Filament\Support\generate_icon_html(GravityIcon::TrashBin, size: IconSize::Small)?->toHtml() !!}
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            @if ($shouldShowFooter)
                <div class="fff-rich-editor__footer">
                    <div class="fff-rich-editor__footer-start">
                        @if ($shouldShowJsonBadge)
                            <span class="fff-rich-editor__footer-badge">{{ $jsonBadgeLabel }}</span>
                        @endif

                        <span
                            x-show="autosaveStatus !== 'idle'"
                            x-cloak
                            class="fff-rich-editor__footer-autosave"
                            x-bind:class="{
                                'fff-rich-editor__footer-autosave--saving': autosaveStatus === 'saving',
                                'fff-rich-editor__footer-autosave--saved': autosaveStatus === 'saved',
                            }"
                            role="status"
                            aria-live="polite"
                            x-bind:aria-busy="autosaveStatus === 'saving'"
                        >
                            <span
                                x-show="autosaveStatus === 'saving'"
                                x-cloak
                                class="fff-rich-editor__footer-autosave-indicator"
                                aria-hidden="true"
                            >
                                {{ \Filament\Support\generate_loading_indicator_html(size: \Filament\Support\Enums\IconSize::Small) }}
                            </span>

                            <span
                                x-show="autosaveStatus === 'saved'"
                                x-cloak
                                class="fff-rich-editor__footer-autosave-indicator fff-rich-editor__footer-autosave-indicator--saved"
                                aria-hidden="true"
                            >
                                <x-filament::icon
                                    icon="heroicon-m-check-circle"
                                    class="fi-icon fi-size-sm"
                                />
                            </span>

                            <span x-text="autosaveStatus === 'saving'
                                ? (footerConfig.labels?.autosaveSaving ?? '')
                                : (footerConfig.labels?.autosaveSaved ?? '')"></span>
                        </span>
                    </div>

                    <div class="fff-rich-editor__footer-end">
                        <span
                            x-show="footerAltMissingCount > 0"
                            x-cloak
                            x-text="`${footerAltMissingCount} {{ __('filament-flex-fields::default.rich_editor.alt_text.missing_suffix') }}`"
                            class="fff-rich-editor__footer-alt-warning"
                            role="status"
                            aria-live="polite"
                        ></span>

                        <span
                            class="fff-rich-editor__footer-stats"
                            x-bind:class="{
                                'fff-rich-editor__footer-stats--warning': footerLimitStatus === 'warning',
                                'fff-rich-editor__footer-stats--danger': footerLimitStatus === 'danger',
                            }"
                            x-text="footerStats"
                            role="status"
                            aria-live="polite"
                            x-bind:aria-label="footerLimitStatus === 'danger'
                                ? (footerConfig.labels?.limitDanger ?? footerStats)
                                : (footerLimitStatus === 'warning'
                                    ? (footerConfig.labels?.limitWarning ?? footerStats)
                                    : footerStats)"
                        ></span>
                    </div>
                </div>
            @endif
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>
