@php
    $pools = $pools ?? [];
    $filters = $filters ?? [];
    $extraLocales = $extraLocales ?? [];
@endphp

@if (count($pools) > 0)
    <template id="fff-country-registry-data" data-locale="{{ app()->getLocale() }}" data-navigate-track>
        @json(\Bjanczak\FilamentFlexFields\Support\CountryRegistry::payload($pools, filters: $filters, extraLocales: $extraLocales))
    </template>
@endif
