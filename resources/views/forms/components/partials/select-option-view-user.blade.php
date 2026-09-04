@php
    use Filament\Support\Enums\IconSize;

    $layout = $layout ?? 'list';
    $avatarName = (string) ($label ?? 'User');
    // Deterministic colour — `background=random` remounted on every Alpine x-html
    // pass and looked like the selected option was swapping to someone else.
    $avatarBackground = substr(hash('crc32b', $avatarName), 0, 6);
    $avatarUrl = filled($image ?? null)
        ? $image
        : 'https://ui-avatars.com/api/?name='.urlencode($avatarName).'&background='.$avatarBackground.'&color=fff&size=64';
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
