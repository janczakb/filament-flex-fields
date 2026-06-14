@php
    use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;

    $id = $getId();
    $key = $getKey(isAbsolute: false);
    $childSchema = $getChildSchema();
    $parentTabs = $getContainer()->getParentComponent();
    $isActivePanel = $parentTabs instanceof SegmentTabs
        && $parentTabs->getActiveTabKey() === $key;
@endphp

@if (! empty($childSchema->getComponents()))
    <div
        @class([
            'fff-segment-tabs__panel',
            'is-active' => $isActivePanel,
        ])
        x-bind:class="{
            'is-active': tab === @js($key),
        }"
        x-on:expand="tab = @js($key)"
        {{
            $attributes
                ->merge([
                    'aria-labelledby' => $id . '-trigger',
                    'id' => $id,
                    'role' => 'tabpanel',
                    'wire:key' => $getLivewireKey() . '.container',
                ], escape: false)
                ->merge($getExtraAttributes(), escape: false)
        }}
    >
        {{ $childSchema }}
    </div>
@endif
