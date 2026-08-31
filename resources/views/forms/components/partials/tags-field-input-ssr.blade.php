{{--
    SSR FlexTextInput shell for TagsField — visible before Alpine hydrates the input row.
--}}
<div
    class="fff-tags-field-input-ssr"
    aria-hidden="true"
>
    <div @class([
        'fff-tags-field__shell fff-flex-text-input__shell',
        'is-invalid' => $hasError,
    ])>
        <div class="fff-tags-field__row fff-flex-text-input__row">
            @if (count($prefixActions) || $prefixIcon || filled($prefixLabel))
                <div class="fff-flex-text-input__prefix-wrap">
                    @if (filled($prefixLabel))
                        <span class="fff-flex-text-input__prefix-label">{{ $prefixLabel }}</span>
                    @endif

                    @if ($prefixIcon)
                        <span class="fff-flex-text-input__prefix-icon" aria-hidden="true">
                            {{ \Filament\Support\generate_icon_html($prefixIcon, color: $prefixIconColor) }}
                        </span>
                    @endif

                    @foreach ($prefixActions as $prefixAction)
                        {{ $prefixAction }}
                    @endforeach
                </div>
            @endif

            <div class="fff-tags-field__control fff-flex-text-input__control">
                <input
                    type="text"
                    class="fff-tags-field__input fff-flex-text-input__input fi-input"
                    @if (filled($placeholder))
                        placeholder="{{ e($placeholder) }}"
                    @endif
                    readonly
                    tabindex="-1"
                />
            </div>

            @if (count($suffixActions) || $suffixIcon || filled($suffixLabel))
                <div class="fff-flex-text-input__suffix-wrap">
                    @foreach ($suffixActions as $suffixAction)
                        {{ $suffixAction }}
                    @endforeach

                    @if ($suffixIcon)
                        <span class="fff-flex-text-input__suffix-icon" aria-hidden="true">
                            {{ \Filament\Support\generate_icon_html($suffixIcon, color: $suffixIconColor) }}
                        </span>
                    @endif

                    @if (filled($suffixLabel))
                        <span class="fff-flex-text-input__suffix-label">{{ $suffixLabel }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
