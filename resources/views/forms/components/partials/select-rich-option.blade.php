@php
    use Filament\Support\Enums\IconSize;

    $layout = $layout ?? 'list';
@endphp

@if ($layout === 'grid')
    <span class="fff-select-option fff-select-option--grid">
        @include('filament-flex-fields::forms.components.partials.select-option-media', [
            'label' => $label,
            'icon' => $icon,
            'image' => $image ?? null,
            'badgeColor' => $badgeColor ?? 'primary',
            'iconSize' => $iconSize ?? IconSize::Medium,
            'mediaClass' => 'fff-select-option__grid-icon',
        ])

        <span class="fff-select-option__grid-label">{{ $label }}</span>
    </span>
@elseif ($layout === 'trigger')
    <span class="fff-select-option fff-select-option--trigger">
        @include('filament-flex-fields::forms.components.partials.select-option-media', [
            'label' => $label,
            'icon' => $icon,
            'image' => $image ?? null,
            'badgeColor' => $badgeColor ?? 'primary',
            'iconSize' => match ($iconSize ?? null) {
                IconSize::Small => IconSize::ExtraSmall,
                IconSize::Large => IconSize::Small,
                default => IconSize::Small,
            },
            'mediaClass' => 'fff-select-option__trigger-icon',
        ])

        <span class="fff-select-option__trigger-label">{{ $label }}</span>
    </span>
@else
    <span class="fff-select-option fff-select-option--list">
        @if (filled($icon) || filled($image ?? null))
            @include('filament-flex-fields::forms.components.partials.select-option-media', [
                'label' => $label,
                'icon' => $icon,
                'image' => $image ?? null,
                'badgeColor' => $badgeColor ?? 'primary',
                'iconSize' => $iconSize ?? IconSize::Medium,
                'mediaClass' => 'fff-select-option__icon',
            ])
        @endif

        <span class="fff-select-option__content">
            <span class="fff-select-option__label">{{ $label }}</span>

            @if (filled($description))
                <span class="fff-select-option__description">{{ $description }}</span>
            @endif
        </span>

        @if (filled($badge))
            <span @class([
                'fff-select-option__badge',
                'fff-select-option__badge--'.($badgeColor ?? 'primary'),
            ])>{{ $badge }}</span>
        @endif
    </span>
@endif
