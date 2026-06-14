@php
    use Filament\Support\Enums\IconSize;

    $iconSize = match ($size) {
        'lg' => IconSize::Medium,
        'sm' => IconSize::Small,
        default => IconSize::Small,
    };
@endphp

<span
    @class([
        'fff-rating-column',
        'fff-rating',
        'fff-rating--'.$size,
        'fi-color-'.$color => filled($color),
        'is-read-only',
        'fff-rating--with-value' => $showValue,
    ])
    aria-label="{{ number_format($value, 1) }} / {{ $max }}"
>
    <span class="fff-rating__items" aria-hidden="true">
        @foreach ($items as $index)
            @php
                $fillPercent = round($fillPercentageFor($index) * 100, 4);
                $isSelected = (int) $value === $index;
            @endphp
            <span
                @class([
                    'fff-rating__item',
                    'fff-rating__item--'.$size,
                ])
                data-active="{{ $fillPercent > 0 ? 'true' : 'false' }}"
                @if ($isSelected)
                    data-selected="true"
                @endif
            >
                <span class="fff-rating__icon-stack">
                    <span class="fff-rating__icon fff-rating__icon--empty">
                        {{ \Filament\Support\generate_icon_html($icon, size: $iconSize) }}
                    </span>

                    <span
                        class="fff-rating__icon-clip"
                        style="width: {{ $fillPercent }}%"
                    >
                        <span class="fff-rating__icon fff-rating__icon--filled">
                            {{ \Filament\Support\generate_icon_html($icon, size: $iconSize) }}
                        </span>
                    </span>
                </span>
            </span>
        @endforeach
    </span>

    @if ($showValue)
        <span class="fff-rating__value">{{ number_format($value, 1) }}</span>
    @endif
</span>
