@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

    $stylesheets = $stylesheets ?? [];
    $chunks = $chunks ?? [];
    $consumerComponent = $consumerComponent ?? null;
    $livewireKey = $livewireKey ?? null;
    $stylesheetHrefs = array_map(
        static fn (string $stylesheet): string => FlexFieldAssets::stylesheetHref($stylesheet),
        $stylesheets,
    );
    $chunkHrefs = array_map(
        static fn (string $chunk): string => FlexFieldAssets::alpineChunkSrc($chunk),
        $chunks,
    );
    $consumerAttributes = ($consumerComponent && filled($livewireKey))
        ? FlexFieldAssets::consumerAttributesFor($livewireKey, $consumerComponent)
        : [];
@endphp

@if (count($stylesheets) > 0 || count($chunks) > 0)
    <span
        hidden
        aria-hidden="true"
        data-fff-asset-batch
        data-fff-stylesheets='@json($stylesheetHrefs)'
        data-fff-chunks='@json($chunkHrefs)'
        @foreach ($consumerAttributes as $attribute => $value)
            {{ $attribute }}="{{ $value }}"
        @endforeach
    ></span>
@endif
