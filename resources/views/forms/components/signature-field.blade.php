@php
    use Filament\Support\Enums\IconSize;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $livewireKey = $getLivewireKey();
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
        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('signature-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
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
                },
            })"
            x-init="init()"
            x-ref="root"
            tabindex="-1"
            x-on:keydown.window.capture="handleGlideKeydown($event)"
            @class([
                'fff-signature__root',
                'is-trackpad-glide' => $field->isTrackpadGlideEnabled(),
            ])
            x-bind:style="{ '--fff-signature-aspect-ratio': aspectRatio }"
        >
            <div
                class="fff-signature__pad"
                x-on:pointerdown="engageGlide()"
                x-bind:class="{ 'has-guidelines': guidelinesEnabled }"
                x-bind:style="guidelinesEnabled && backgroundColor ? { backgroundColor } : null"
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
                    x-text="labels.placeholder"
                ></p>

                <div class="fff-signature__toolbar" x-show="! readOnly">
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

                    <button
                        type="button"
                        class="fff-signature__action"
                        x-show="canDownload"
                        x-on:click="downloadSignature()"
                        x-bind:aria-label="labels.download"
                    >
                        <span class="fff-signature__action-icon" aria-hidden="true">
                            {{ \Filament\Support\generate_icon_html($field->getDownloadIcon(), size: IconSize::Small) }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="fff-signature__action"
                        x-show="fullscreenEnabled"
                        x-on:click="openFullscreen()"
                        x-bind:aria-label="labels.fullscreen"
                    >
                        <span class="fff-signature__action-icon" aria-hidden="true">
                            {{ \Filament\Support\generate_icon_html($field->getFullscreenIcon(), size: IconSize::Small) }}
                        </span>
                    </button>
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
                    </div>

                    <div
                        class="fff-signature__modal-pad"
                        x-on:pointerdown="engageGlide()"
                        x-bind:class="{ 'has-guidelines': guidelinesEnabled }"
                        x-bind:style="guidelinesEnabled && backgroundColor ? { backgroundColor } : null"
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
