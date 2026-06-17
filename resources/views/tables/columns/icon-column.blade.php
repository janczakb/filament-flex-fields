<span
    @class([
        'fff-icon-column',
        'fff-icon-column--' . $size,
        'fff-icon-column--with-label' => $showLabel || $showName,
        'fi-color-' . $color => filled($color) && is_string($color),
    ])
    @if ($showLabel || $showName)
        title="{{ $showName ? $icon : $label }}"
    @endif
>
    <span class="fff-icon-column__icon" aria-hidden="true">
        {!! $iconHtml !!}
    </span>

    @if ($showLabel || $showName)
        <span class="fff-icon-column__text">
            @if ($showLabel && filled($label))
                <span class="fff-icon-column__label">{{ $label }}</span>
            @endif

            @if ($showName)
                <span class="fff-icon-column__name">{{ $icon }}</span>
            @endif
        </span>
    @endif
</span>
