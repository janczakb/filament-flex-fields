{{--
    SSR FlexTextInput shell — visible before Alpine hydrates the barcode scanner field.
    Prevents layout shift on playground reload (same pattern as icon/select SSR triggers).
--}}
@php
    use Filament\Support\Enums\IconSize;

    $displayValue = filled($initialValue) ? $initialValue : ($placeholder ?? '');
    $isPlaceholder = blank($initialValue);
@endphp

<div
    @class([
        'fff-barcode-scanner-input-ssr',
        'fff-flex-text-input',
        'fff-barcode-scanner--'.$size,
        'fff-flex-text-input--'.$size,
        'fff-barcode-scanner--'.$variant,
        'fff-flex-text-input--'.$variant,
        'has-actions' => true,
        'has-focus-outline' => $hasFocusOutline,
        'is-manual-disabled' => ! $allowsManualInput,
    ])
    aria-hidden="true"
>
    <div class="fff-flex-text-input__shell">
        <div class="fff-flex-text-input__row">
            <div class="fff-flex-text-input__control">
                <x-filament::input.wrapper
                    :disabled="$disabled"
                    :valid="true"
                    :attributes="
                        \Filament\Support\prepare_inherited_attributes(new \Illuminate\View\ComponentAttributeBag())
                            ->class(['fff-flex-text-input__wrapper'])
                    "
                >
                    <input
                        type="text"
                        class="fff-flex-text-input__input fi-input"
                        @if ($isPlaceholder)
                            placeholder="{{ $placeholder }}"
                        @else
                            value="{{ $displayValue }}"
                        @endif
                        readonly
                        tabindex="-1"
                    />
                </x-filament::input.wrapper>
            </div>

            <div class="fff-flex-text-input__action-group" aria-hidden="true">
                <div class="fff-flex-text-input__action-item fff-flex-text-input__scan">
                    <span class="fff-flex-text-input__action-btn fff-flex-text-input__action-btn--scan">
                        {{ \Filament\Support\generate_icon_html($scanIcon, size: IconSize::Small, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'fff-flex-text-input__action-icon'])) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
