@php
    use Bjanczak\FilamentFlexFields\Support\CountryRegistry;
    use Bjanczak\FilamentFlexFields\Support\CalculatorPanelMount;
    use Bjanczak\FilamentFlexFields\Support\CountryRegistryQueue;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAlpineQueue;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;

    if ($component === 'country-field') {
        CountryRegistryQueue::enqueue(CountryRegistry::POOL_ISO);
    }

    if ($component === 'phone-field') {
        CountryRegistryQueue::enqueue(CountryRegistry::POOL_PHONE);
    }

    if ($component === 'calculator-field') {
        CalculatorPanelMount::queue();
    }

    $pendingStylesheets = FlexFieldStylesheetQueue::enqueueFor($component);
    $pendingChunks = FlexFieldAlpineQueue::enqueueChunksFor($component);
@endphp

@if (count($pendingStylesheets) > 0 || count($pendingChunks) > 0)
    @include('filament-flex-fields::partials.emit-assets', [
        'stylesheets' => $pendingStylesheets,
        'chunks' => $pendingChunks,
        'consumerComponent' => $component,
        'livewireKey' => $livewireKey ?? null,
    ])

    @php
        FlexFieldStylesheetQueue::markStylesheetsEmitted($pendingStylesheets);
        FlexFieldAlpineQueue::markChunksEmitted($pendingChunks);
    @endphp
@endif
