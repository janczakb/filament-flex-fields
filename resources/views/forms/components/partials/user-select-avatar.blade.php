@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;

    $sizeClass = match ($layout ?? 'list') {
        'trigger' => 'fff-user-select__avatar--trigger',
        'tag' => 'fff-user-select__avatar--tag',
        'stack' => 'fff-user-select__avatar--stack',
        default => 'fff-user-select__avatar--list',
    };
@endphp

<span @class([
    'fff-user-select__avatar',
    $sizeClass,
]) aria-hidden="true">
    <span class="fff-user-select__avatar-surface">
        @if (filled($image))
            <img src="{{ $image }}" alt="" class="fff-user-select__avatar-image" loading="lazy" />
        @else
            <span class="fff-user-select__avatar-initials">{{ $initials }}</span>
        @endif
    </span>

    @if ($verified)
        <span class="fff-user-select__verified-badge" title="{{ __('Verified') }}" aria-hidden="true">
            {{ \Filament\Support\generate_icon_html(GravityIcon::SealCheck, size: IconSize::ExtraSmall) }}
        </span>
    @endif
</span>
