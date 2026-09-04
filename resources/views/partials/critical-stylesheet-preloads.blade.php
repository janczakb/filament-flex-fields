@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

    // overlay-runtime + teleported-menu when select/icon-picker fields queue overlay CSS.
    $preloads = FlexFieldAssets::criticalPreloadStylesheets();
@endphp

@if (count($preloads) > 0)
    @foreach ($preloads as $stylesheet)
        <link
            rel="preload"
            href="{{ FlexFieldAssets::stylesheetHref($stylesheet) }}"
            as="style"
            data-fff-preload-stylesheet="{{ $stylesheet }}"
        />
    @endforeach
@endif
