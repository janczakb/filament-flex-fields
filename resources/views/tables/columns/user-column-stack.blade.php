<span
    class="fff-user-column fff-user-column--stacked"
    style="--fff-user-column-stack-overlap: {{ (int) $overlap }}px; --fff-user-column-stack-ring: {{ (int) $ring }}px;"
>
    <span class="fff-user-column__avatar-stack" role="list">
        @foreach ($users as $user)
            <span
                class="fff-user-column__avatar-stack-item"
                role="listitem"
                @if ($showTooltips)
                    title="{{ $user['label'] }}"
                @endif
            >
                @include('filament-flex-fields::forms.components.partials.user-select-avatar', [
                    'image' => $user['image'] ?? null,
                    'initials' => $user['initials'] ?? '',
                    'verified' => false,
                    'layout' => 'stack',
                ])
            </span>
        @endforeach

        @if ($overflow > 0)
            <span class="fff-user-column__avatar-stack-overflow" title="{{ __(':count more users', ['count' => $overflow]) }}">
                +{{ $overflow }}
            </span>
        @endif
    </span>
</span>
