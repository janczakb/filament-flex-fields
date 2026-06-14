@php
    use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
    use Illuminate\Support\Js;

    $id = $getId();
    $label = $getLabel();
    $size = $getSize();
    $variant = $getVariant();
    $color = $getColor();
    $hasSeparators = $hasSeparators();
    $isFullWidth = $isFullWidth();
    $isIconOnly = $isIconOnly();
    $expandSelectedLabel = $shouldExpandSelectedLabel();
    $tabs = $getVisibleTabs();
    $activeTabIndex = $getActiveTab();
    $activeTabKey = $getActiveTabKey();
    $tabKeys = collect($tabs)->map(static fn (SegmentTab $tab): string => $tab->getKey(isAbsolute: false))->values()->all();

    $getTabVisibilityJs = function (SegmentTab $tab): ?string {
        $hiddenJs = $tab->getHiddenJs();
        $visibleJs = $tab->getVisibleJs();

        return match ([filled($hiddenJs), filled($visibleJs)]) {
            [true, true] => "(! ({$hiddenJs})) && ({$visibleJs})",
            [true, false] => "! ({$hiddenJs})",
            [false, true] => $visibleJs,
            default => null,
        };
    };
@endphp

<div
    x-data="{
        tab: @if ($isTabPersisted() && filled($id)) $persist(@js($activeTabKey)).as(@js($id)) @else @js($activeTabKey) @endif,
        optionKeys: {{ Js::from($tabKeys) }},
        separators: @js($hasSeparators),
        indicatorStyle: '',
        indicatorAnimated: false,
        resizeObserver: null,
        boundResetHandler: null,
        unsubscribeLivewireHook: null,
        normalize(value) {
            return value === null || value === undefined ? null : String(value);
        },
        isSelected(value) {
            return this.normalize(this.tab) === this.normalize(value);
        },
        select(value) {
            this.tab = value;
            this.$nextTick(() => this.updateIndicator());
        },
        selectedIndex() {
            const current = this.normalize(this.tab);

            return this.optionKeys.findIndex((key) => this.normalize(key) === current);
        },
        showSeparator(separatorIndex) {
            if (! this.separators) {
                return false;
            }

            const selectedIndex = this.selectedIndex();

            if (selectedIndex === -1) {
                return true;
            }

            return separatorIndex !== selectedIndex - 1 && separatorIndex !== selectedIndex;
        },
        separatorClass(separatorIndex) {
            return this.showSeparator(separatorIndex) ? '' : 'is-hidden';
        },
        updateIndicator() {
            const track = this.$refs.track;

            if (! track) {
                return;
            }

            const selected = track.querySelector('[data-segment-selected=true]');

            if (! selected) {
                this.indicatorStyle = 'opacity: 0;';

                return;
            }

            this.indicatorStyle =
                'width: ' + selected.offsetWidth + 'px;' +
                'height: ' + selected.offsetHeight + 'px;' +
                'transform: translate3d(' + selected.offsetLeft + 'px, ' + selected.offsetTop + 'px, 0);' +
                'opacity: 1;';
        },
        enableIndicatorAnimation() {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.indicatorAnimated = true;
                });
            });
        },
        updateQueryString() {
            @if ($isTabPersistedInQueryString())
                const url = new URL(window.location.href);
                url.searchParams.set(@js($getTabQueryStringKey()), this.tab);
                history.replaceState(null, document.title, url.toString());
            @endif
        },
        init() {
            @if ($isTabPersistedInQueryString())
                const queryString = new URLSearchParams(window.location.search);
                const queryTab = queryString.get(@js($getTabQueryStringKey()));

                if (queryTab && this.optionKeys.includes(queryTab)) {
                    this.tab = queryTab;
                }
            @endif

            if (! this.tab || ! this.optionKeys.includes(this.tab)) {
                this.tab = this.optionKeys[@js(max(0, $activeTabIndex - 1))] ?? this.optionKeys[0] ?? null;
            }

            this.$watch('tab', () => {
                this.updateQueryString();
                this.$nextTick(() => this.updateIndicator());
            });

            this.$nextTick(() => {
                this.updateIndicator();
                this.enableIndicatorAnimation();
            });

            if (typeof ResizeObserver !== 'undefined' && this.$refs.track) {
                this.resizeObserver = new ResizeObserver(() => this.updateIndicator());
                this.resizeObserver.observe(this.$refs.track);
            }

            this.unsubscribeLivewireHook = Livewire.interceptMessage(({ message, onSuccess }) => {
                onSuccess(() => {
                    this.$nextTick(() => {
                        if (message.component.id !== @js($this->getId())) {
                            return;
                        }

                        if (! this.optionKeys.includes(this.tab)) {
                            this.tab = this.optionKeys[@js(max(0, $activeTabIndex - 1))] ?? this.tab;
                        }

                        this.updateIndicator();
                    });
                });
            });

            this.boundResetHandler = (event) => {
                if (
                    event.detail.livewireId !== @js($this->getId()) ||
                    event.detail.schemaKey !== @js($getRootContainer()->getKey()) ||
                    @js($isTabPersisted()) ||
                    @js($isTabPersistedInQueryString())
                ) {
                    return;
                }

                this.$nextTick(() => {
                    this.tab = this.optionKeys[@js(max(0, $activeTabIndex - 1))] ?? this.tab;
                    this.updateIndicator();
                });
            };

            window.addEventListener('reset-schema-component-state', this.boundResetHandler);
        },
        destroy() {
            this.unsubscribeLivewireHook?.();
            this.resizeObserver?.disconnect();

            if (this.boundResetHandler) {
                window.removeEventListener('reset-schema-component-state', this.boundResetHandler);
            }
        },
    }"
    x-init="init()"
    wire:ignore.self
    {{
        $attributes
            ->merge([
                'id' => $id,
                'wire:key' => $getLivewireKey() . '.container',
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fff-segment-tabs',
                'w-full' => $isFullWidth,
            ])
    }}
>
    <div
        @class([
            'fff-segment-control',
            'fff-segment-tabs__control',
            'w-full' => $isFullWidth,
            'fi-color-'.$color => filled($color),
        ])
        role="tablist"
        @if (filled($label))
            aria-label="{{ $label }}"
        @endif
    >
        <div
            x-ref="track"
            @class([
                'fff-segment-track',
                'fff-segment-track--'.$size,
                'fff-segment-track--ghost' => $variant === 'ghost',
            ])
            x-bind:class="{ 'is-animated': indicatorAnimated }"
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
    </div>

    @foreach ($tabs as $tab)
        @php
            $tabVisibilityJs = $getTabVisibilityJs($tab);
        @endphp

        @if ($tabVisibilityJs)
            <div x-cloak x-show="{!! $tabVisibilityJs !!}">
                {{ $tab }}
            </div>
        @else
            {{ $tab }}
        @endif
    @endforeach
</div>
