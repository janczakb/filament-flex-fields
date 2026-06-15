@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

    $playgroundStylesheetHref = FlexFieldAssets::playgroundStylesheetHrefForRequest();
    $playgroundSlug = FlexFieldAssets::resolvePlaygroundSlugFromRequest();
@endphp

@if (filled($playgroundStylesheetHref))
    <link
        rel="stylesheet"
        href="{{ $playgroundStylesheetHref }}"
        data-navigate-track
        data-fff-playground-bundle
        @if (filled($playgroundSlug))
            data-fff-playground-slug="{{ $playgroundSlug }}"
        @endif
    />
@endif
