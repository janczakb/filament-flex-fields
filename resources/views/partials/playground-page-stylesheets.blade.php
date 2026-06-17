@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;

    $playgroundStylesheetHref = FlexFieldAssets::playgroundStylesheetHrefForRequest();
    $playgroundSlug = FlexFieldAssets::resolvePlaygroundSlugFromRequest();

    if (
        filled($playgroundSlug)
        && FlexFieldAssets::hasPlaygroundBundleForSlug($playgroundSlug)
    ) {
        FlexFieldStylesheetQueue::suppressForPlaygroundBundle(
            FlexFieldAssets::playgroundStylesheetsFor($playgroundSlug),
        );
    }
@endphp

@if (filled($playgroundStylesheetHref))
    @push('styles')
        <link
            rel="stylesheet"
            href="{{ $playgroundStylesheetHref }}"
            data-navigate-track
            data-fff-playground-bundle
            @if (filled($playgroundSlug))
                data-fff-playground-slug="{{ $playgroundSlug }}"
            @endif
        />
    @endpush
@endif
