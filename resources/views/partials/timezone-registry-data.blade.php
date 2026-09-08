@php
    $pools = $pools ?? [];
    $filters = $filters ?? [];
    $extraLocales = $extraLocales ?? [];
@endphp

@if (count($pools) > 0)
    <template id="fff-timezone-registry-data" data-locale="{{ app()->getLocale() }}" data-navigate-track>
        @json(\Bjanczak\FilamentFlexFields\Support\TimezoneRegistry::payload($pools, filters: $filters, extraLocales: $extraLocales))
    </template>
@endif
