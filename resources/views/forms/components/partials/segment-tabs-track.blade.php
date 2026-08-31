<div
    x-ref="track"
    @class([
        'fff-segment-track',
        'fff-segment-track--'.$size,
        'fff-segment-track--ghost' => $variant === 'ghost',
        'fff-segment-track--in-shell' => $inShell,
    ])
    x-bind:class="{ 'is-animated': indicatorAnimated, 'is-hydrated': indicatorHydrated }"
>
    <div
        x-ref="indicator"
        aria-hidden="true"
        @class([
            'fff-segment-indicator',
            'fff-segment-indicator--ghost' => $variant === 'ghost',
        ])
        x-bind:class="{ 'is-animated': indicatorAnimated }"
        x-bind:style="indicatorStyle"
    ></div>

    @foreach ($tabs as $tab)
        @php
            $tabKey = $tab->getKey(isAbsolute: false);
            $tabLabel = $tab->getLabel();
            $tabIcon = $tab->getIcon();
            $tabTooltip = $tab->getTooltip();
            $tabBadge = $tab->getBadge();
            $tabBadgeColor = filled($tabBadge) ? $tab->getBadgeColor($tabBadge) : null;
            $tabBadgeTooltip = filled($tabBadge) ? $tab->getBadgeTooltip($tabBadge) : null;
            $tabVisibilityJs = $getTabVisibilityJs($tab);
            $isActiveTab = $isTabActive($tab);
        @endphp

        @if (! $loop->first && $hasSeparators)
            <span
                class="fff-segment-separator"
                x-bind:class="separatorClass({{ $loop->index - 1 }})"
                aria-hidden="true"
            ></span>
        @endif

        <button
            type="button"
            role="tab"
            @class([
                'fff-segment-item',
                'fff-segment-item--'.$size,
            ])
            data-segment-value="{{ $tabKey }}"
            data-segment-selected="{{ $isActiveTab ? 'true' : 'false' }}"
            aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
            x-bind:data-segment-selected="isSelected(@js($tabKey)) ? 'true' : 'false'"
            x-bind:aria-selected="isSelected(@js($tabKey)) ? 'true' : 'false'"
            aria-controls="{{ $tab->getId() }}"
            id="{{ $tab->getId() }}-trigger"
            x-on:click="select(@js($tabKey))"
            @if ($tabVisibilityJs)
                x-cloak
                x-show="{!! $tabVisibilityJs !!}"
            @endif
            @if (filled($tabTooltip))
                x-tooltip="{ content: @js($tabTooltip), theme: $store.theme }"
            @endif
        >
            @if (filled($tabIcon))
                <x-filament::icon :icon="$tabIcon" />
            @endif

            @if ($isIconOnly)
                <span class="sr-only">{{ $tabLabel }}</span>
            @elseif ($expandSelectedLabel)
                <span
                    @unless ($isActiveTab)
                        x-show="isSelected(@js($tabKey))"
                        x-cloak
                    @endunless
                >{{ $tabLabel }}</span>
            @else
                <span class="fff-segment-item__label">{{ $tabLabel }}</span>
            @endif

            @if (filled($tabBadge))
                <x-filament::badge
                    :color="$tabBadgeColor"
                    size="xs"
                    :tooltip="$tabBadgeTooltip"
                    class="fff-segment-item__badge"
                >
                    {{ $tabBadge }}
                </x-filament::badge>
            @endif
        </button>
    @endforeach
</div>
