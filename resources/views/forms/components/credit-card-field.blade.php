@php
    use Bjanczak\FilamentFlexFields\Support\CreditCardDisplay;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $wrapperClasses = $getWrapperClasses();
    $contactlessClipId = 'fff-credit-card-contactless-'.md5($statePath);

    $previewState = $field->normalizeState(is_array($getState()) ? $getState() : []);
    $previewDigits = CreditCardDisplay::sanitizeDigits($previewState['number']);

    $previewBrand = 'unknown';

    if ($previewDigits !== '') {
        if (preg_match('/^4/', $previewDigits)) {
            $previewBrand = 'visa';
        } elseif (preg_match('/^(5[1-5]|2(2[2-9]\d{2}|[3-6]\d{3}|7[01]\d{2}|720))/', $previewDigits)) {
            $previewBrand = 'mastercard';
        }
    }

    $previewName = trim($previewState['name']) !== '' ? strtoupper(trim($previewState['name'])) : 'YOUR NAME';
    $previewExpiry = trim($previewState['expiry']) !== '' ? $previewState['expiry'] : 'MM/YY';
    $previewNumber = CreditCardDisplay::maskedNumber($previewDigits);
    $inputNumber = CreditCardDisplay::formatNumberInput($previewState['number']);
    $inputName = $previewState['name'];
    $inputExpiry = $previewState['expiry'];
    $inputCvv = $previewState['cvv'];
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'credit-card'])
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('credit-card', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="creditCardFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            initialNumber: @js($previewState['number']),
            initialName: @js($previewState['name']),
            initialExpiry: @js($previewState['expiry']),
            initialCvv: @js($previewState['cvv']),
            flipOnCvvFocus: @js($shouldFlipOnCvvFocus()),
            disabled: @js($isDisabled),
        })"
        x-init="init()"
        @class([
            'fff-credit-card',
            'fff-credit-card--'.$getSize(),
            'fff-credit-card--'.$getVariant(),
            'fff-credit-card--input-'.$getInputVariant(),
            'is-disabled' => $isDisabled,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div class="fff-credit-card__preview" wire:ignore>
            <div
                class="fff-credit-card__scene"
                x-bind:class="{ 'is-flipped': isFlipped }"
            >
                <div class="fff-credit-card__inner">
                    <div
                        @class([
                            'fff-credit-card__face',
                            'fff-credit-card__front',
                            'is-brand-'.$previewBrand,
                        ])
                        x-bind:class="'is-brand-' + brand"
                    >
                        <div class="fff-credit-card__shine" aria-hidden="true"></div>
                        <div class="fff-credit-card__mesh" aria-hidden="true"></div>

                        <div class="fff-credit-card__header">
                            <div class="fff-credit-card__mark">{{ $getMark() }}</div>

                            <svg class="fff-credit-card__contactless" viewBox="0 0 20 24" aria-hidden="true">
                                <g clip-path="url(#{{ $contactlessClipId }})">
                                    <path d="M15.1429 1.28571C17.0236 4.54326 18.0138 8.23849 18.0138 12C18.0138 15.7615 17.0236 19.4567 15.1429 22.7143M10.4286 3.64285C11.8956 6.18374 12.6679 9.06602 12.6679 12C12.6679 14.934 11.8956 17.8162 10.4286 20.3571M5.92859 5.80713C6.98933 7.66394 7.54777 9.77022 7.54777 11.9143C7.54777 14.0583 6.98933 16.1646 5.92859 18.0214M1.42859 8.14285C2.19306 9.29983 2.59834 10.6362 2.59834 12C2.59834 13.3638 2.19306 14.7002 1.42859 15.8571" fill="none" stroke="currentColor" stroke-width="2.57143" stroke-linecap="round" />
                                </g>
                                <defs>
                                    <clipPath id="{{ $contactlessClipId }}">
                                        <rect width="20" height="24" fill="white" />
                                    </clipPath>
                                </defs>
                            </svg>
                        </div>

                        <div class="fff-credit-card__footer">
                            <div class="fff-credit-card__details">
                                <div class="fff-credit-card__meta">
                                    <span class="fff-credit-card__name" x-text="displayName">{{ $previewName }}</span>
                                    <span class="fff-credit-card__expiry" x-text="displayExpiry">{{ $previewExpiry }}</span>
                                </div>

                                <span class="fff-credit-card__number" x-text="displayNumber">{{ $previewNumber }}</span>
                            </div>

                            <div class="fff-credit-card__brand-badge" aria-hidden="true">
                                <svg class="fff-credit-card__brand-logo is-visa" xmlns="http://www.w3.org/2000/svg" viewBox="0 7.8 24 7.6" aria-hidden="true">
                                    <path d="M13.967 13.837c-.766 0-1.186-.105-1.831-.37l-.239-.109-.271 1.575c.466.192 1.306.357 2.175.37 2.041 0 3.375-.947 3.391-2.404.016-.801-.51-1.409-1.621-1.91-.674-.325-1.094-.543-1.094-.873 0-.292.359-.603 1.109-.603.645-.01 1.096.127 1.455.269l.18.08.271-1.522-.047.01c-.387-.144-.99-.297-1.74-.297-1.92 0-3.275.954-3.285 2.321-.012 1.005.964 1.571 1.701 1.908.757.345 1.01.562 1.008.872C15.124 13.625 14.524 13.837 13.967 13.837zM22.428 8.182h-1.5c-.467 0-.816.125-1.021.583l-2.885 6.44h2.041l.408-1.054 2.49.002c.061.246.24 1.052.24 1.052H24L22.428 8.182zM20.03 12.71l.774-1.963c-.01.02.16-.406.258-.67l.133.606.449 2.027H20.03z" fill="currentColor" />
                                    <polygon points="8.444 15.149 10.388 15.149 11.603 8.123 9.66 8.123 9.66 8.121" fill="currentColor" />
                                    <path d="M4.923,12.971l-0.202-0.976v0.003L4.039,8.772C3.922,8.325,3.58,8.193,3.156,8.177H0.025L0,8.325C0.705,8.49,1.34,8.729,1.908,9.022c0.102,0.063,0.145,0.132,0.18,0.234l1.68,5.939h2.054l3.061-7.013H6.824L4.923,12.971z" fill="currentColor" />
                                </svg>

                                <svg class="fff-credit-card__brand-logo is-mastercard" viewBox="0 0 30 19" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M14.9053 16.4393C13.3266 17.77 11.2787 18.5733 9.04092 18.5733C4.04776 18.5733 0 14.5737 0 9.64C0 4.70625 4.04776 0.706665 9.04092 0.706665C11.2787 0.706665 13.3266 1.51 14.9053 2.84072C16.484 1.51 18.5319 0.706665 20.7697 0.706665C25.7629 0.706665 29.8106 4.70625 29.8106 9.64C29.8106 14.5737 25.7629 18.5733 20.7697 18.5733C18.5319 18.5733 16.484 17.77 14.9053 16.4393Z" fill="#ED0006" />
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M14.9053 16.4393C16.8492 14.8007 18.0818 12.3626 18.0818 9.64C18.0818 6.91739 16.8492 4.47925 14.9053 2.84072C16.484 1.50999 18.5319 0.706665 20.7697 0.706665C25.7628 0.706665 29.8106 4.70625 29.8106 9.64C29.8106 14.5737 25.7628 18.5733 20.7697 18.5733C18.5319 18.5733 16.484 17.77 14.9053 16.4393Z" fill="#F9A000" />
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M14.9053 16.4393C16.8492 14.8008 18.0818 12.3627 18.0818 9.64007C18.0818 6.91748 16.8492 4.47936 14.9053 2.84082C12.9614 4.47936 11.7288 6.91748 11.7288 9.64007C11.7288 12.3627 12.9614 14.8008 14.9053 16.4393Z" fill="#FF5E00" />
                                </svg>

                                <span class="fff-credit-card__brand-fallback">CARD</span>
                            </div>
                        </div>
                    </div>

                    <div class="fff-credit-card__face fff-credit-card__back">
                        <div class="fff-credit-card__stripe" aria-hidden="true"></div>
                        <div class="fff-credit-card__signature">
                            <div class="fff-credit-card__signature-line" aria-hidden="true"></div>
                            <div class="fff-credit-card__cvv-box">
                                <span class="fff-credit-card__label">{{ __('filament-flex-fields::default.credit_card.cvv') }}</span>
                                <span class="fff-credit-card__cvv-value" x-text="displayCvv">{{ CreditCardDisplay::maskedCvv('') }}</span>
                            </div>
                        </div>
                        <p class="fff-credit-card__legal">{{ __('filament-flex-fields::default.credit_card.authorized_signature') }}</p>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="fff-credit-card__flip-btn"
                x-on:click="flipCard()"
                :disabled="disabled"
            >
                {{ __('filament-flex-fields::default.credit_card.flip') }}
            </button>
        </div>

        <div class="fff-credit-card__fields">
            <label class="fff-credit-card__field fff-credit-card__field--full">
                <span class="fff-credit-card__field-label">{{ $getNumberLabel() }}</span>
                <input
                    type="text"
                    inputmode="numeric"
                    autocomplete="cc-number"
                    class="fff-credit-card__input"
                    value="{{ $inputNumber }}"
                    :value="formattedNumberInput"
                    :disabled="disabled"
                    placeholder="1234 5678 9012 3456"
                    x-on:input="onNumberInput($event)"
                />
            </label>

            <label class="fff-credit-card__field fff-credit-card__field--full">
                <span class="fff-credit-card__field-label">{{ $getNameLabel() }}</span>
                <input
                    type="text"
                    autocomplete="cc-name"
                    class="fff-credit-card__input"
                    value="{{ $inputName }}"
                    :value="resolvedName"
                    :disabled="disabled"
                    placeholder="Jan Kowalski"
                    x-on:input="onNameInput($event)"
                />
            </label>

            <div class="fff-credit-card__field-row">
                <label class="fff-credit-card__field">
                    <span class="fff-credit-card__field-label">{{ $getExpiryLabel() }}</span>
                    <input
                        type="text"
                        inputmode="numeric"
                        autocomplete="cc-exp"
                        class="fff-credit-card__input"
                        value="{{ $inputExpiry }}"
                        :value="resolvedExpiry"
                        :disabled="disabled"
                        placeholder="MM/YY"
                        maxlength="5"
                        x-on:input="onExpiryInput($event)"
                    />
                </label>

                <label class="fff-credit-card__field">
                    <span class="fff-credit-card__field-label">{{ $getCvvLabel() }}</span>
                    <input
                        type="password"
                        inputmode="numeric"
                        autocomplete="cc-csc"
                        class="fff-credit-card__input"
                        value="{{ $inputCvv }}"
                        :value="resolvedCvv"
                        :disabled="disabled"
                        placeholder="123"
                        maxlength="4"
                        x-on:input="onCvvInput($event)"
                        x-on:focus="onCvvFocus()"
                        x-on:blur="onCvvBlur()"
                    />
                </label>
            </div>
        </div>
    </div>
</x-dynamic-component>
