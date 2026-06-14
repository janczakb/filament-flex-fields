@foreach (\Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue::enqueueFor($component) as $stylesheet)
    <link
        rel="stylesheet"
        href="{{ \Bjanczak\FilamentFlexFields\Support\FlexFieldAssets::stylesheetHref($stylesheet) }}"
        data-navigate-track
    />
@endforeach

@foreach (\Bjanczak\FilamentFlexFields\Support\FlexFieldAlpineQueue::enqueueChunksFor($component) as $chunk)
    <link
        rel="modulepreload"
        href="{{ \Bjanczak\FilamentFlexFields\Support\FlexFieldAssets::alpineChunkSrc($chunk) }}"
        data-navigate-track
    />
@endforeach
