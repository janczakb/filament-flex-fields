{{--
    Lazy component assets are queued when a field renders, then pushed to Filament's
    @stack('styles') in <head> (panels::layout.base). FlexFieldStylesheetQueue and
    FlexFieldAlpineQueue deduplicate across duplicate fields on the same request.
--}}
@php
    use Bjanczak\FilamentFlexFields\Support\CountryRegistry;
    use Bjanczak\FilamentFlexFields\Support\CountryRegistryQueue;

    if ($component === 'country-field') {
        CountryRegistryQueue::enqueue(CountryRegistry::POOL_ISO);
    }

    if ($component === 'phone-field') {
        CountryRegistryQueue::enqueue(CountryRegistry::POOL_PHONE);
    }
@endphp
@foreach (\Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue::enqueueFor($component) as $stylesheet)
    @pushOnce('styles', 'bjanczak-flex-fields:stylesheet:'.$stylesheet)
        <link
            rel="stylesheet"
            href="{{ \Bjanczak\FilamentFlexFields\Support\FlexFieldAssets::stylesheetHref($stylesheet) }}"
            data-navigate-track
        />
    @endPushOnce
@endforeach

@foreach (\Bjanczak\FilamentFlexFields\Support\FlexFieldAlpineQueue::enqueueChunksFor($component) as $chunk)
    @pushOnce('styles', 'bjanczak-flex-fields:alpine:'.$chunk)
        <link
            rel="modulepreload"
            href="{{ \Bjanczak\FilamentFlexFields\Support\FlexFieldAssets::alpineChunkSrc($chunk) }}"
            data-navigate-track
        />
    @endPushOnce
@endforeach
