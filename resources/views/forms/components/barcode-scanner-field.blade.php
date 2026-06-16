@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Facades\FilamentAsset;
    use Illuminate\View\ComponentAttributeBag;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $hasError = filled($statePath) && $errors->has($statePath);
    $livewireKey = $getLivewireKey();
    $config = $field->getAlpineConfiguration();
    $initialState = \Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableHydrator::resolveRenderedState($field);
    $initialValue = \Bjanczak\FilamentFlexFields\Support\Barcode\BarcodeStateNormalizer::extractValue($initialState) ?? '';
    $scanIcon = $getScanIcon();
    $modalId = 'fff-barcode-scanner-'.$getId();
    $modalWindowAttributes = (new ComponentAttributeBag())->class(['fff-barcode-scanner-modal__window']);
    $modalOverlayAttributes = (new ComponentAttributeBag())->class(['fff-barcode-scanner-modal__overlay']);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'barcode-scanner-field'])

    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $getVariant(), $getSupportedFormats(), $getCameraFacing(), $getScanInterval(), $allowsCameraSwitch(), $getPreferredDeviceId(), $shouldStoreDetectedFormat(), $shouldPauseWhenHidden()])), 0, 64) }}"
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('barcode-scanner-field', FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="barcodeScannerFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            modalId: @js($modalId),
            initialValue: @js($initialValue),
            disabled: @js($isDisabled),
            readOnly: @js($isReadOnly),
            ...@js($config),
        })"
        x-init="init()"
        x-on:close-modal.window="onScannerModalClosed($event)"
        x-on:modal-closed.window="onScannerModalClosed($event)"
        x-on:x-modal-opened.window="onScannerModalOpened($event)"
        @class([
            'fff-barcode-scanner',
            'fff-flex-text-input',
            'fff-barcode-scanner--'.$getSize(),
            'fff-flex-text-input--'.$getSize(),
            'fff-barcode-scanner--'.$getVariant(),
            'fff-flex-text-input--'.$getVariant(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
            'is-manual-disabled' => ! $allowsManualInput(),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div @class([
            'fff-flex-text-input__shell',
            'is-invalid' => $hasError,
        ])>
            <div class="fff-flex-text-input__row">
                <div class="fff-flex-text-input__control">
                    <x-filament::input.wrapper
                        :disabled="$isDisabled"
                        :inline-suffix="true"
                        :valid="! $hasError"
                        :attributes="
                            \Filament\Support\prepare_inherited_attributes(new \Illuminate\View\ComponentAttributeBag())
                                ->class(['fff-flex-text-input__wrapper'])
                        "
                    >
                        <input
                            type="text"
                            inputmode="text"
                            autocomplete="off"
                            autocapitalize="off"
                            spellcheck="false"
                            class="fff-flex-text-input__input fi-input fi-input-has-inline-suffix"
                            placeholder="{{ $getPlaceholder() }}"
                            x-ref="input"
                            x-model="inputValue"
                            x-on:input="onManualInput()"
                            x-on:blur="onManualBlur()"
                            @disabled($isDisabled || ! $allowsManualInput())
                            @readonly($isReadOnly || ! $allowsManualInput())
                            aria-describedby="{{ $allowsManualInput() ? null : $getId().'-manual-hint' }}"
                        />

                        <x-slot name="suffix">
                            <button
                                type="button"
                                class="fff-barcode-scanner__scan-btn"
                                x-on:click="openScanner()"
                                x-bind:disabled="disabled || readOnly"
                                x-bind:aria-label="labels.scan"
                                aria-haspopup="dialog"
                                x-bind:aria-controls="@js($modalId)"
                            >
                                {{ \Filament\Support\generate_icon_html($scanIcon, size: IconSize::Small) }}
                                <span class="sr-only" x-text="labels.scan"></span>
                            </button>
                        </x-slot>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>

        @unless ($allowsManualInput())
            <p id="{{ $getId() }}-manual-hint" class="fff-barcode-scanner__hint">
                {{ __('filament-flex-fields::default.barcode_scanner.manual_only') }}
            </p>
        @endunless

        <x-filament::modal
            :id="$modalId"
            :heading="$getModalHeading()"
            :description="__('filament-flex-fields::default.barcode_scanner.modal_description')"
            :icon="$scanIcon"
            icon-color="primary"
            width="lg"
            teleport="body"
            :close-button="true"
            :close-by-clicking-away="true"
            :close-by-escaping="true"
            class="fff-barcode-scanner-modal"
            :extra-modal-window-attribute-bag="$modalWindowAttributes"
            :extra-modal-overlay-attribute-bag="$modalOverlayAttributes"
        >
            <div
                class="fff-barcode-scanner__stage"
                x-bind:class="{
                    'is-ready': scannerReady,
                    'is-success': scanSuccess,
                    'is-error': scannerError,
                    'is-reduced-motion': reducedMotion,
                }"
                x-bind:aria-busy="scannerReady ? 'false' : 'true'"
            >
                <div class="fff-barcode-scanner__viewport">
                    <video
                        id="{{ $modalId }}-video"
                        class="fff-barcode-scanner__video"
                        playsinline
                        webkit-playsinline
                        autoplay
                        muted
                        aria-hidden="true"
                    ></video>

                    <div class="fff-barcode-scanner__viewport-shade" aria-hidden="true"></div>

                    <div
                        class="fff-barcode-scanner__reticle"
                        x-show="scannerReady"
                        x-cloak
                        aria-hidden="true"
                    >
                        <span class="fff-barcode-scanner__scan-line"></span>
                    </div>

                    <div
                        class="fff-barcode-scanner__success-flash"
                        x-show="scanSuccess"
                        x-cloak
                        aria-hidden="true"
                    >
                        <span class="fff-barcode-scanner__success-icon">
                            {{ \Filament\Support\generate_icon_html('heroicon-o-check-circle', size: IconSize::Large) }}
                        </span>
                    </div>

                    <div
                        class="fff-barcode-scanner__loading"
                        x-show="! scannerReady && ! scannerError"
                        aria-live="polite"
                    >
                        <span class="fff-barcode-scanner__spinner" aria-hidden="true"></span>
                        <span>{{ __('filament-flex-fields::default.barcode_scanner.starting_camera') }}</span>
                    </div>

                    <div
                        class="fff-barcode-scanner__engine-badge"
                        x-show="scannerReady && scannerEngine"
                        x-cloak
                        aria-hidden="true"
                    >
                        <span
                            x-text="scannerEngine === 'native' ? labels.engineNative : labels.engineZxing"
                        ></span>
                    </div>
                </div>

                <div
                    class="fff-barcode-scanner__toolbar"
                    x-show="scannerReady && ! scannerError"
                    x-cloak
                >
                    <button
                        type="button"
                        class="fff-barcode-scanner__switch-camera-btn"
                        x-show="canSwitchCamera"
                        x-on:click="switchCamera()"
                        x-bind:aria-label="labels.switchCamera"
                    >
                        <span x-text="labels.switchCamera"></span>
                    </button>

                    <button
                        type="button"
                        class="fff-barcode-scanner__torch-btn"
                        x-show="torchSupported"
                        x-on:click="toggleTorch()"
                        x-bind:aria-pressed="torchEnabled ? 'true' : 'false'"
                        x-bind:aria-label="torchEnabled ? labels.torchOff : labels.torchOn"
                    >
                        <span x-text="torchEnabled ? labels.torchOff : labels.torchOn"></span>
                    </button>
                </div>

                <div class="fff-barcode-scanner__meta">
                    <p
                        class="fff-barcode-scanner__hint"
                        x-show="scannerReady && ! scannerError && ! scanSuccess"
                        x-cloak
                    >
                        <span x-text="labels.scanHint"></span>
                    </p>

                    <p
                        class="fff-barcode-scanner__success-copy"
                        x-show="scanSuccess"
                        x-cloak
                        x-text="labels.scanSuccess"
                        role="status"
                    ></p>

                    <p
                        class="fff-barcode-scanner__error"
                        x-show="scannerError"
                        x-cloak
                        x-text="scannerError"
                        role="alert"
                    ></p>
                </div>
            </div>
        </x-filament::modal>
    </div>
</x-dynamic-component>
