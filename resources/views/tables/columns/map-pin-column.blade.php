@php
    use Filament\Support\Enums\IconSize;

    $iconSize = match ($size) {
        'sm' => IconSize::Small,
        'lg' => IconSize::Medium,
        default => IconSize::Small,
    };
@endphp

<span
    @class([
        'fff-map-pin-column',
        'fff-map-pin-column--' . $size,
        'fff-map-pin-column--with-label' => $showLabel,
    ])
    @if (filled($coordinates))
        title="{{ $coordinates }}"
    @endif
>
    <span class="fff-map-pin-column__icon" aria-hidden="true">
        {{ \Filament\Support\generate_icon_html($icon, size: $iconSize) }}
    </span>

    @if ($showLabel)
        <span class="fff-map-pin-column__label">{{ $label }}</span>
    @endif
</span>
