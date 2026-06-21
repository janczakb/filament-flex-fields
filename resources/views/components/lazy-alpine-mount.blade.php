@props(['mountImmediately' => false, 'eager' => false])

@if ($eager)
    <div {{ $attributes }}>
        {{ $slot }}
    </div>
@else
    <div
        x-data="{ shouldMount: @js($mountImmediately) }"
        x-intersect:enter.once.margin.300px="shouldMount = true"
    >
        <template x-if="shouldMount">
            <div {{ $attributes }}>
                {{ $slot }}
            </div>
        </template>
    </div>
@endif
