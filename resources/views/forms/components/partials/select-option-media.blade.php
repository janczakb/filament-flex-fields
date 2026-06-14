@php
    use Filament\Support\Enums\IconSize;

    $mediaClass = $mediaClass ?? 'fff-select-option__media';
@endphp

<span @class([
    $mediaClass,
    $mediaClass.'--'.($badgeColor ?? 'primary'),
]) aria-hidden="true">
    @if (filled($image))
        <img src="{{ $image }}" alt="" class="fff-select-option__image" loading="lazy" />
    @elseif (filled($icon))
        {{ \Filament\Support\generate_icon_html($icon, size: $iconSize ?? IconSize::Medium) }}
    @else
        <span class="fff-select-option__fallback">{{ mb_strtoupper(mb_substr($label, 0, 1)) }}</span>
    @endif
</span>
