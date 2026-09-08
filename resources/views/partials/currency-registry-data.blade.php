@php
    $pools = $pools ?? [];
    $filters = $filters ?? [];
@endphp

@if (count($pools) > 0)
    <template id="fff-currency-registry-data" data-locale="{{ app()->getLocale() }}" data-navigate-track>
        @json(\Bjanczak\FilamentFlexFields\Support\CurrencyRegistry::payload($pools, filters: $filters))
    </template>
@endif
