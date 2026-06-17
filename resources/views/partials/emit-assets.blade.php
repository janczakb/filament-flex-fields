@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
    use Livewire\Livewire;

    $stylesheets = $stylesheets ?? [];
    $chunks = $chunks ?? [];
    $stylesheetHrefs = array_map(
        static fn (string $stylesheet): string => FlexFieldAssets::stylesheetHref($stylesheet),
        $stylesheets,
    );
    $chunkHrefs = array_map(
        static fn (string $chunk): string => FlexFieldAssets::alpineChunkSrc($chunk),
        $chunks,
    );
@endphp

@if (count($stylesheets) > 0 || count($chunks) > 0)
    <span
        hidden
        data-fff-asset-batch
        data-fff-stylesheets='@json($stylesheetHrefs)'
        data-fff-chunks='@json($chunkHrefs)'
    ></span>

    @if (Livewire::isLivewireRequest())
        @foreach ($stylesheets as $stylesheet)
            <link
                rel="stylesheet"
                href="{{ FlexFieldAssets::stylesheetHref($stylesheet) }}"
                data-navigate-track
                data-fff-stylesheet="{{ $stylesheet }}"
            />
        @endforeach

        @foreach ($chunks as $chunk)
            <link
                rel="modulepreload"
                href="{{ FlexFieldAssets::alpineChunkSrc($chunk) }}"
                data-navigate-track
                data-fff-alpine-chunk="{{ $chunk }}"
            />
        @endforeach
    @else
        @push('styles')
            @foreach ($stylesheets as $stylesheet)
                <link
                    rel="stylesheet"
                    href="{{ FlexFieldAssets::stylesheetHref($stylesheet) }}"
                    data-navigate-track
                    data-fff-stylesheet="{{ $stylesheet }}"
                />
            @endforeach

            @foreach ($chunks as $chunk)
                <link
                    rel="modulepreload"
                    href="{{ FlexFieldAssets::alpineChunkSrc($chunk) }}"
                    data-navigate-track
                    data-fff-alpine-chunk="{{ $chunk }}"
                />
            @endforeach
        @endpush
    @endif
@endif
