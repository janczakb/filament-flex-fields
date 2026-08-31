<span
    @class([
        'fff-progress-column',
        'fff-progress-column--' . $size,
        'fff-progress-bar',
        'fff-progress-bar--' . $size,
        'fi-color-' . $color => filled($color),
        'is-fill-static',
        'fff-progress-column--with-value' => $showValue,
    ])
    role="progressbar"
    aria-valuemin="0"
    aria-valuemax="100"
    aria-valuenow="{{ round($percentage, 1) }}"
>
    @if ($showValue || filled($label))
        <span class="fff-progress-column__header">
            @if (filled($label))
                <span class="fff-progress-column__label">{{ $label }}</span>
            @endif

            @if ($showValue)
                <span class="fff-progress-column__value">{{ number_format($percentage, 0) }}%</span>
            @endif
        </span>
    @endif

    <span class="fff-progress-bar__track">
        <span class="fff-progress-bar__rail" aria-hidden="true">
            <span
                class="fff-progress-bar__fill"
                style="--fff-progress-bar-fill: {{ round($percentage, 4) }}%"
            ></span>
        </span>
    </span>
</span>
