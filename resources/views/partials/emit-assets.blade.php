@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

    $stylesheets = $stylesheets ?? [];
    $chunks = $chunks ?? [];
    $consumerComponent = $consumerComponent ?? null;
    $livewireKey = $livewireKey ?? null;

    // Defense in depth: every non-empty batch must carry CRG consumer attrs.
    // Keyless schema components previously emitted consumer-less markers; the injector
    // loaded CSS then uninstalled it (~150ms) — production forms broke, playground did not.
    if (! filled($consumerComponent) && (count($stylesheets) > 0 || count($chunks) > 0)) {
        $consumerComponent = $stylesheets[0] ?? $chunks[0] ?? 'asset-batch';
    }

    $stylesheetHrefs = array_map(
        static fn (string $stylesheet): string => FlexFieldAssets::stylesheetHref($stylesheet),
        $stylesheets,
    );
    $chunkHrefs = array_map(
        static fn (string $chunk): string => FlexFieldAssets::alpineChunkSrc($chunk),
        $chunks,
    );
    $consumerAttributes = filled($consumerComponent)
        ? FlexFieldAssets::consumerAttributesForComponent((string) $consumerComponent, $livewireKey)
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
