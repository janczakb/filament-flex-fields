{{--
    SSR trigger overlay — mirrors the hydrated icon picker trigger until Alpine marks it replaced.
    Prevents layout shift on page load (same pattern as select-field-headless).
--}}
<div
    @class([
        'fff-select-trigger-ssr',
        'fff-icon-picker-trigger-ssr',
        'fi-select-input-ctn' => $showClearButton,
        'fi-select-input-ctn-clearable' => $showClearButton,
        'fff-select-trigger-ssr--clearable' => $showClearButton,
    ])
    aria-hidden="true"
>
    <span class="fff-select-trigger-ssr__btn">
        <span class="fff-select-trigger-ssr__value-ctn fi-select-input-value-ctn">
            <span @class([
                'fi-select-input-value-label',
                'fi-select-input-placeholder' => $isInitialPlaceholder,
            ])>
                @if ($hasInitialSelection)
                    <span class="fff-icon-picker__preview">{!! $initialSelectedHtml !!}</span>
                @endif
                <span class="fff-icon-picker__name">{{ $hasInitialSelection ? $initialState : $placeholder }}</span>
            </span>
        </span>
    </span>

    @if ($showClearButton)
        <button
            type="button"
            class="fi-select-input-value-remove-btn"
            tabindex="-1"
            aria-hidden="true"
            disabled
        >
            {!! $clearIconHtml !!}
        </button>
    @endif
</div>
