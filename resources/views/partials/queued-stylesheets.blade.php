@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAlpineQueue;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;

    $stylesheets = FlexFieldStylesheetQueue::pending();
    $chunks = FlexFieldAlpineQueue::pending();
@endphp

@if (count($stylesheets) > 0 || count($chunks) > 0)
    @include('filament-flex-fields::partials.emit-assets', [
        'stylesheets' => $stylesheets,
        'chunks' => $chunks,
    ])

    @php
        FlexFieldStylesheetQueue::markStylesheetsEmitted($stylesheets);
        FlexFieldAlpineQueue::markChunksEmitted($chunks);
    @endphp
@endif
