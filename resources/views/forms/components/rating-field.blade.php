@php
    use Filament\Support\Enums\IconSize;

    $statePath = $getStatePath();
    $max = $getMax();
    $size = $getSize();
    $color = $getColor();
    $icon = $getIcon();
    $items = $getItemIndexes();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $shouldShowValue = $shouldShowValue();
    $canInteract = ! $isDisabled && ! $isReadOnly;
    $inputName = $getId().'-rating';
    $iconSize = match ($size) {
        'sm' => IconSize::Small,
        'lg' => IconSize::Large,
        default => IconSize::Medium,
    };

    $initialNumeric = is_numeric($getState()) ? (float) $getState() : null;
    $initialDisplayValue = $initialNumeric ?? 0;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('rating-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="ratingFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            disabled: @js($isDisabled),
            readOnly: @js($isReadOnly),
            canInteract: @js($canInteract),
            max: @js($max),
        })"
        x-init="init()"
        @class([
            'fff-rating',
            'fff-rating--'.$size,
            'fi-color-'.$color => filled($color),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'fff-rating--with-value' => $isReadOnly && $shouldShowValue,
            'is-interactive' => $canInteract,
        ])
        role="radiogroup"
        aria-label="{{ $getLabel() }}"
        aria-orientation="horizontal"
        {{ $getExtraAlpineAttributeBag() }}
    >
        <div class="fff-rating__items">
            @foreach ($items as $index)
                @php
                    $initialFillPercent = round(max(0, min(1, $initialDisplayValue - ($index - 1))) * 100, 4);
                    $isInitiallySelected = $initialNumeric !== null && (int) $initialNumeric === $index;
                @endphp
                <label
                    @class([
                        'fff-rating__item',
                        'fff-rating__item--'.$size,
                    ])
                    data-active="{{ $initialFillPercent > 0 ? 'true' : 'false' }}"
                    @if ($isInitiallySelected)
                        data-selected="true"
                    @endif
                    x-bind:data-active="fillFor({{ $index }}) > 0 ? 'true' : 'false'"
                    x-bind:data-selected="isSelected({{ $index }}) ? 'true' : 'false'"
                    @if ($canInteract)
                        x-on:mouseenter="preview({{ $index }})"
                        x-on:mouseleave="clearPreview()"
                        x-on:click.prevent="select({{ $index }})"
                    @endif
                >
                    @if ($canInteract)
                        <span class="sr-only">
                            <input
                                type="radio"
                                name="{{ $inputName }}"
                                value="{{ $index }}"
                                x-bind:checked="isSelected({{ $index }})"
                                x-bind:disabled="disabled || readOnly"
                                tabindex="-1"
                                aria-label="{{ trans_choice('filament-flex-fields::default.rating.stars', $index, ['count' => $index]) }}"
                            />
                        </span>
                    @endif

                    <span class="fff-rating__icon-stack" aria-hidden="true">
                        <span class="fff-rating__icon fff-rating__icon--empty">
                            {{ \Filament\Support\generate_icon_html($icon, size: $iconSize) }}
                        </span>

                        <span
                            class="fff-rating__icon-clip"
                            style="width: {{ $initialFillPercent }}%"
                            x-bind:style="'width: ' + (fillFor({{ $index }}) * 100) + '%'"
                        >
                            <span class="fff-rating__icon fff-rating__icon--filled">
                                {{ \Filament\Support\generate_icon_html($icon, size: $iconSize) }}
                            </span>
                        </span>
                    </span>
                </label>
            @endforeach
        </div>

        @if ($isReadOnly && $shouldShowValue)
            <span
                class="fff-rating__value"
                x-text="displayValue().toFixed(1)"
            >{{ number_format($initialDisplayValue, 1) }}</span>
        @endif
    </div>
</x-dynamic-component>
