@php
    use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
    use Illuminate\Support\Js;

    $id = $getId();
    $tabPersistKey = $getTabPersistKey();
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
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('segment-tabs', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
    x-data="segmentTabsSchemaComponent({
        activeTabIndex: @js(max(0, $activeTabIndex - 1)),
        activeTabKey: @js($activeTabKey),
        optionKeys: {{ Js::from($tabKeys) }},
        separators: @js($hasSeparators),
        isTabPersisted: @js($isTabPersisted() && filled($id)),
        tabPersistKey: @js($tabPersistKey),
        isTabPersistedInQueryString: @js($isTabPersistedInQueryString()),
        isTabPersistedFlag: @js($isTabPersisted()),
        tabQueryStringKey: @js($getTabQueryStringKey()),
        livewireId: @js($this->getId()),
        schemaKey: @js($getRootContainer()->getKey()),
        overflowShell: @js(! $isFullWidth),
    })"
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
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'segment-tabs'])

    @if ($isFullWidth)
        <div
            @class([
                'fff-segment-control',
                'fff-segment-tabs__control',
                'w-full',
                'fi-color-'.$color => filled($color),
            ])
            role="tablist"
            @if (filled($label))
                aria-label="{{ $label }}"
            @endif
        >
            @include('filament-flex-fields::forms.components.partials.segment-tabs-track', [
                'tabs' => $tabs,
                'size' => $size,
                'variant' => $variant,
                'hasSeparators' => $hasSeparators,
                'isIconOnly' => $isIconOnly,
                'expandSelectedLabel' => $expandSelectedLabel,
                'getTabVisibilityJs' => $getTabVisibilityJs,
                'isTabActive' => $isTabActive,
                'inShell' => false,
            ])
        </div>
    @else
        <x-filament-flex-fields::segment-overflow-shell
            :variant="$variant"
            :tablist-label="$label"
            tablist-role="tablist"
        >
            <div
                @class([
                    'fff-segment-control',
                    'fff-segment-tabs__control',
                    'fi-color-'.$color => filled($color),
                ])
            >
                @include('filament-flex-fields::forms.components.partials.segment-tabs-track', [
                    'tabs' => $tabs,
                    'size' => $size,
                    'variant' => $variant,
                    'hasSeparators' => $hasSeparators,
                    'isIconOnly' => $isIconOnly,
                    'expandSelectedLabel' => $expandSelectedLabel,
                    'getTabVisibilityJs' => $getTabVisibilityJs,
                    'isTabActive' => $isTabActive,
                    'inShell' => true,
                ])
            </div>
        </x-filament-flex-fields::segment-overflow-shell>
    @endif

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
