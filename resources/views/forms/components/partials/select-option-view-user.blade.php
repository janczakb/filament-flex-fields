@php
    use Filament\Support\Enums\IconSize;

    $layout = $layout ?? 'list';
    $avatarUrl = filled($image ?? null)
        ? $image
        : 'https://ui-avatars.com/api/?name='.urlencode($label).'&background=random&size=64';
@endphp

@if ($layout === 'trigger' || $layout === 'chip')
    <span class="fff-select-option fff-select-option--trigger fff-select-option-view-user">
        @include('filament-flex-fields::forms.components.partials.select-option-media', [
            'label' => $label,
            'icon' => $icon ?? null,
            'image' => $avatarUrl,
            'badgeColor' => $badgeColor ?? 'primary',
            'iconSize' => IconSize::Small,
            'mediaClass' => 'fff-select-option__trigger-icon fff-select-option-view-user__avatar',
        ])

        <span class="fff-select-option__trigger-label">{{ $label }}</span>
    </span>
@else
    <span class="fff-select-option fff-select-option--list fff-select-option-view-user">
        @include('filament-flex-fields::forms.components.partials.select-option-media', [
            'label' => $label,
            'icon' => $icon ?? null,
            'image' => $avatarUrl,
            'badgeColor' => $badgeColor ?? 'primary',
            'iconSize' => IconSize::Medium,
            'mediaClass' => 'fff-select-option__icon fff-select-option-view-user__avatar',
        ])

        <span class="fff-select-option__content">
            <span class="fff-select-option__label">{{ $label }}</span>

            @if (filled($description))
                <span class="fff-select-option__description">{{ $description }}</span>
            @endif
        </span>
    </span>
@endif
