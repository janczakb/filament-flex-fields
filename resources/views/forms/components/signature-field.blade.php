@php
    use Filament\Support\Enums\IconSize;
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Filament\Support\Facades\FilamentAsset;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $livewireKey = $getLivewireKey();
    $hasError = filled($statePath) && $errors->has($statePath);
    $alpineConfig = $field->getAlpineConfiguration($isRequired());
    $signatureAssetSrc = FilamentAsset::getAlpineComponentSrc('signature-field', FilamentFlexFieldsPlugin::PACKAGE_NAME);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($field->getWrapperClasses())
    "
>
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $field->getPenColor(), $field->getPenWidth(), $field->isFullscreenEnabled(), $field->isTrackpadGlideEnabled(), $field->isTrackpadGlideEnabled() ? $field->getTrackpadGlideKey() : null, $field->isGuidelinesEnabled()])), 0, 64) }}"
        @class([
            'fff-signature',
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'is-empty' => blank($getState()),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'signature-field'])
        @if ($field->requiresLegalInk())
            <div class="fff-signature__legal-banner" role="note">
                {{ __('filament-flex-fields::default.signature.legal_acknowledgment') }}
            </div>
        @endif
        @if (filled($signatureAssetSrc))
            <link
                rel="modulepreload"
                href="{{ $signatureAssetSrc }}"
                as="script"
            />
        @endif
        <div
            x-load
            x-load-src="{{ $signatureAssetSrc }}"
            x-data="signatureFieldFormComponent({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                penColor: @js($field->getPenColor()),
                penWidth: @js($field->getPenWidth()),
                backgroundColor: @js($field->getBackgroundColor()),
                viewBoxWidth: @js($field->getViewBoxWidth()),
                viewBoxHeight: @js($field->getViewBoxHeight()),
                readOnly: @js($isDisabled || $isReadOnly),
                fullscreenEnabled: @js($field->isFullscreenEnabled()),
                undoable: @js($field->isUndoable()),
                smoothingEnabled: @js($field->isSmoothingEnabled()),
                trackpadGlideEnabled: @js($field->isTrackpadGlideEnabled()),
                trackpadGlideKey: @js($field->isTrackpadGlideEnabled() ? $field->getTrackpadGlideKey() : 'd'),
                guidelinesEnabled: @js($field->isGuidelinesEnabled()),
                downloadFormat: @js($field->getDownloadFormat()),
                downloadFilename: @js($field->getDownloadFilename()),
                webpQuality: @js($field->getWebpQuality()),
                storedSvg: @js($field->getStoredSvgContent()),
                minStrokes: @js($alpineConfig['minStrokes']),
                maxSizeKb: @js($alpineConfig['maxSizeKb']),
                required: @js($alpineConfig['required']),
                validationMessages: @js($alpineConfig['validationMessages']),
                inkTrailEnabled: @js($alpineConfig['inkTrailEnabled']),
                pdfPreviewEnabled: @js($alpineConfig['pdfPreviewEnabled']),
                legalAcknowledgment: @js($alpineConfig['legalAcknowledgment']),
                labels: {
                    placeholder: @js($field->isTrackpadGlideEnabled()
                        ? __('filament-flex-fields::default.signature.trackpad_placeholder')
                        : __('filament-flex-fields::default.signature.placeholder')),
                    clear: @js(__('filament-flex-fields::default.signature.clear')),
                    undo: @js(__('filament-flex-fields::default.signature.undo')),
                    fullscreen: @js(__('filament-flex-fields::default.signature.fullscreen')),
                    download: @js(__('filament-flex-fields::default.signature.download')),
                    done: @js(__('filament-flex-fields::default.signature.done')),
                    close: @js(__('filament-flex-fields::default.signature.close')),
                    trackpad_pill_paused: @js(__('filament-flex-fields::default.signature.trackpad_pill_paused')),
                    trackpad_pill_active: @js(__('filament-flex-fields::default.signature.trackpad_pill_active')),
                    pdf_preview: @js(__('filament-flex-fields::default.signature.pdf_preview')),
                    pdf_preview_title: @js(__('filament-flex-fields::default.signature.pdf_preview_title')),
                    pdf_preview_body: @js(__('filament-flex-fields::default.signature.pdf_preview_body')),
                },
            })"
            x-init="init()"
            x-ref="root"
            tabindex="-1"
            x-on:keydown.window.capture="handleGlideKeydown($event)"
            @class([
                'fff-signature__root',
                'is-trackpad-glide' => $field->isTrackpadGlideEnabled(),
                'has-validation-error' => $hasError,
            ])
            style="--fff-signature-aspect-ratio: {{ $field->getViewBoxWidth() }} / {{ $field->getViewBoxHeight() }};"
            x-bind:class="{ 'is-ready': displayReady }"
        >
            <div
                class="fff-signature__pad"
                x-on:pointerdown="engageGlide()"
                x-bind:class="{ 'has-guidelines': guidelinesEnabled }"
                x-bind:style="padInlineBackgroundStyle"
            >
                <div
                    class="fff-signature__guidelines"
                    x-show="guidelinesEnabled"
                    x-cloak
                    aria-hidden="true"
                ></div>

                @if ($field->isTrackpadGlideEnabled() && ! $isDisabled && ! $isReadOnly)
                <button
                    type="button"
                    class="fff-signature__glide-pill"
                    x-show="showGlidePill"
                    x-cloak
                    x-bind:class="{ 'is-active': glideArmed }"
                    x-on:click.stop="toggleGlideFromPill()"
                    x-bind:aria-pressed="glideArmed"
                    x-bind:aria-label="glidePillText"
                >
                    <span x-text="glidePillText"></span>
                    <kbd class="fff-signature__glide-pill-key" x-text="trackpadGlideKeyLabel"></kbd>
                </button>
                @endif

                <canvas
                    x-ref="canvas"
                    class="fff-signature__canvas"
                    x-bind:class="{ 'is-read-only': readOnly }"
                    x-on:pointerdown="onPointerDown($event)"
                    x-on:pointermove="onPointerMove($event)"
                    x-on:pointerup="onPointerUp($event)"
                    x-on:pointercancel="onPointerCancel($event)"
                    x-on:pointerleave="onPointerLeave($event)"
                    x-bind:aria-label="labels.placeholder"
                    role="img"
                ></canvas>

                <p
                    class="fff-signature__placeholder"
                    x-show="! hasSignature && ! isDrawing"
                    x-cloak
                    x-text="labels.placeholder"
                ></p>

                <div class="fff-signature__dock">
                    <p
                        class="fff-signature__validation"
                        x-show="clientValidationError"
                        x-cloak
                        x-text="clientValidationError"
                        role="alert"
                    ></p>

                @if (! $isDisabled && ! $isReadOnly)
                <div class="fff-signature__toolbar">
                    <span
                        class="fff-signature__action-wrap"
                        x-tooltip="{ content: labels.undo, theme: $store.theme }"
                    >
                        <button
                            type="button"
                            class="fff-signature__action"
                            x-on:click="undo()"
                            x-bind:disabled="! canUndo"
                            x-bind:aria-label="labels.undo"
                        >
                            <span class="fff-signature__action-icon" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getUndoIcon(), size: IconSize::Small) }}
                            </span>
                        </button>
                    </span>

                    <span
                        class="fff-signature__action-wrap"
                        x-tooltip="{ content: labels.clear, theme: $store.theme }"
                    >
                        <button
                            type="button"
                            class="fff-signature__action"
                            x-on:click="clear()"
                            x-bind:disabled="! canClear"
                            x-bind:aria-label="labels.clear"
                        >
                            <span class="fff-signature__action-icon" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getClearIcon(), size: IconSize::Small) }}
                            </span>
                        </button>
                    </span>

                    @if ($field->isPdfPreviewEnabled())
                    <span
                        class="fff-signature__action-wrap"
                        x-tooltip="{ content: labels.pdf_preview, theme: $store.theme }"
                    >
                        <button
                            type="button"
                            class="fff-signature__action"
                            x-on:click="openPdfPreview()"
                            x-bind:aria-label="labels.pdf_preview"
                        >
                            <span class="fff-signature__action-icon" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getPdfPreviewIcon(), size: IconSize::Small) }}
                            </span>
                        </button>
                    </span>
                    @endif

                    @if (filled($field->getDownloadFormat()))
                    <span
                        class="fff-signature__action-wrap"
                        x-show="canDownload"
                        x-cloak
                        x-tooltip="{ content: labels.download, theme: $store.theme }"
                    >
                        <button
                            type="button"
                            class="fff-signature__action"
                            x-on:click="downloadSignature()"
                            x-bind:aria-label="labels.download"
                        >
                            <span class="fff-signature__action-icon" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getDownloadIcon(), size: IconSize::Small) }}
                            </span>
                        </button>
                    </span>
                    @endif

                    @if ($field->isFullscreenEnabled())
                    <span
                        class="fff-signature__action-wrap"
                        x-tooltip="{ content: labels.fullscreen, theme: $store.theme }"
                    >
                        <button
                            type="button"
                            class="fff-signature__action"
                            x-on:click="openFullscreen()"
                            x-bind:aria-label="labels.fullscreen"
                        >
                            <span class="fff-signature__action-icon" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getFullscreenIcon(), size: IconSize::Small) }}
                            </span>
                        </button>
                    </span>
                    @endif
                </div>
                @endif
                </div>
            </div>

            <div
                class="fff-signature__pdf-modal"
                x-show="isPdfPreviewOpen"
                x-cloak
                x-on:keydown.escape.window="closePdfPreview()"
            >
                <div class="fff-signature__modal-backdrop" x-on:click="closePdfPreview()"></div>

                <div
                    class="fff-signature__pdf-panel"
                    role="dialog"
                    aria-modal="true"
                    x-bind:aria-label="labels.pdf_preview_title"
                >
                    <div class="fff-signature__modal-header">
                        <p class="fff-signature__modal-title" x-text="labels.pdf_preview_title"></p>

                        <span
                            class="fff-signature__action-wrap"
                            x-tooltip="{ content: labels.close, theme: $store.theme }"
                        >
                            <button
                                type="button"
                                class="fff-signature__modal-close"
                                x-on:click="closePdfPreview()"
                                x-bind:aria-label="labels.close"
                            >
                                <span class="fff-signature__modal-close-icon" aria-hidden="true">
                                    {{ \Filament\Support\generate_icon_html($field->getCloseIcon(), size: IconSize::Small) }}
                                </span>
                            </button>
                        </span>
                    </div>

                    <div class="fff-signature__pdf-body">
                        <p class="fff-signature__pdf-copy" x-text="labels.pdf_preview_body"></p>

                        <div class="fff-signature__pdf-document">
                            <div class="fff-signature__pdf-signature-slot" x-html="previewSvgMarkup"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="fff-signature__modal"
                x-show="isFullscreen"
                x-cloak
                x-on:keydown.escape.window="closeFullscreen()"
            >
                <div class="fff-signature__modal-backdrop" x-on:click="closeFullscreen()"></div>

                <div
                    class="fff-signature__modal-panel"
                    role="dialog"
                    aria-modal="true"
                    x-bind:aria-label="labels.fullscreen"
                >
                    <div class="fff-signature__modal-header">
                        <p class="fff-signature__modal-title">{{ $getLabel() }}</p>

                        <span
                            class="fff-signature__action-wrap"
                            x-tooltip="{ content: labels.close, theme: $store.theme }"
                        >
                            <button
                                type="button"
                                class="fff-signature__modal-close"
                                x-on:click="closeFullscreen()"
                                x-bind:aria-label="labels.close"
                            >
                                <span class="fff-signature__modal-close-icon" aria-hidden="true">
                                    {{ \Filament\Support\generate_icon_html($field->getCloseIcon(), size: IconSize::Small) }}
                                </span>
                            </button>
                        </span>
                    </div>

                    <div
                        class="fff-signature__modal-pad"
                        x-on:pointerdown="engageGlide()"
                        x-bind:class="{ 'has-guidelines': guidelinesEnabled }"
                        x-bind:style="padInlineBackgroundStyle"
                    >
                        <div
                            class="fff-signature__guidelines"
                            x-show="guidelinesEnabled"
                            x-cloak
                            aria-hidden="true"
                        ></div>

                        <button
                            type="button"
                            class="fff-signature__glide-pill"
                            x-show="showGlidePill"
                            x-cloak
                            x-bind:class="{ 'is-active': glideArmed }"
                            x-on:click.stop="toggleGlideFromPill()"
                            x-bind:aria-pressed="glideArmed"
                            x-bind:aria-label="glidePillText"
                        >
                            <span x-text="glidePillText"></span>
                            <kbd class="fff-signature__glide-pill-key" x-text="trackpadGlideKeyLabel"></kbd>
                        </button>

                        <canvas
                            x-ref="fullscreenCanvas"
                            class="fff-signature__canvas"
                            x-on:pointerdown="onPointerDown($event)"
                            x-on:pointermove="onPointerMove($event)"
                            x-on:pointerup="onPointerUp($event)"
                            x-on:pointercancel="onPointerCancel($event)"
                            x-on:pointerleave="onPointerLeave($event)"
                            x-bind:aria-label="labels.placeholder"
                            role="img"
                        ></canvas>

                        <p
                            class="fff-signature__placeholder"
                            x-show="! hasSignature && ! isDrawing"
                            x-text="labels.placeholder"
                        ></p>
                    </div>

                    <div class="fff-signature__modal-actions">
                        <button
                            type="button"
                            class="fff-signature__modal-button is-secondary"
                            x-on:click="undo()"
                            x-bind:disabled="! canUndo"
                        >
                            <span x-text="labels.undo"></span>
                        </button>

                        <button
                            type="button"
                            class="fff-signature__modal-button is-secondary"
                            x-on:click="clear()"
                            x-bind:disabled="! canClear"
                        >
                            <span x-text="labels.clear"></span>
                        </button>

                        <button
                            type="button"
                            class="fff-signature__modal-button is-primary"
                            x-on:click="confirmFullscreen()"
                        >
                            <span x-text="labels.done"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
