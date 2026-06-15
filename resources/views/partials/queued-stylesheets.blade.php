@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;

    $queuedStylesheets = FlexFieldStylesheetQueue::registered();
@endphp

@if (count($queuedStylesheets) > 0)
    @foreach ($queuedStylesheets as $stylesheet)
        <link
            rel="stylesheet"
            href="{{ FlexFieldAssets::stylesheetHref($stylesheet) }}"
            data-navigate-track
            data-fff-queued-stylesheet="{{ $stylesheet }}"
        />
    @endforeach
@endif
