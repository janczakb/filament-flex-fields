@props(['mountImmediately' => false, 'eager' => false, 'mountOnInteraction' => false, 'wrapSlot' => true])

@if ($eager)
    <div {{ $attributes }}>
        {{ $slot }}
    </div>
@else
    <div
        x-data="{ shouldMount: @js($mountImmediately) }"
        x-intersect:enter.once.margin.300px="shouldMount = true"
        @if ($mountOnInteraction)
            x-on:click="shouldMount = true"
            x-on:focusin="shouldMount = true"
        @endif
        {{ $attributes->class(['fff-lazy-alpine-gate']) }}
    >
        @if ($wrapSlot)
            <template x-if="shouldMount">
                <div {{ $attributes->except('class') }}>
                    {{ $slot }}
                </div>
            </template>
        @else
            {{ $slot }}
        @endif
    </div>
@endif
