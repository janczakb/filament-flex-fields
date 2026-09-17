@php
    use Bjanczak\FilamentFlexFields\Support\CountryRegistry;
    use Bjanczak\FilamentFlexFields\Support\CalculatorPanelMount;
    use Bjanczak\FilamentFlexFields\Support\CountryRegistryQueue;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAlpineQueue;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
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
    $resolvedLivewireKey = FlexFieldAssets::resolveAssetConsumerLivewireKey(
        $component,
        $livewireKey ?? null,
    );
@endphp

{{-- Emit the full root URL set (not only newly pending) so each field instance CRG-owns
     every dependency — shared deps stay retained if a sibling/modal consumer releases.
     resolveAssetConsumerLivewireKey() guarantees a non-blank CRG id even for keyless schema
     components (ItemCardStack::make()). --}}
@if (count($pendingStylesheets) > 0 || count($pendingChunks) > 0)
    @php
        $stylesheets = FlexFieldAssets::stylesheetsFor($component);
        $chunks = FlexFieldAssets::alpineChunksFor($component);
    @endphp

    @include('filament-flex-fields::partials.emit-assets', [
        'stylesheets' => $stylesheets,
        'chunks' => $chunks,
        'consumerComponent' => $component,
        'livewireKey' => $resolvedLivewireKey,
    ])

    @php
        FlexFieldStylesheetQueue::markStylesheetsEmitted($stylesheets);
        FlexFieldAlpineQueue::markChunksEmitted($chunks);
    @endphp
@endif
