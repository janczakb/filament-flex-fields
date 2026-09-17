@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAlpineQueue;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;

    $stylesheets = FlexFieldStylesheetQueue::pending();
    $chunks = FlexFieldAlpineQueue::pending();
    $rootConsumers = FlexFieldStylesheetQueue::pendingConsumers();
    $orphanStylesheets = FlexFieldStylesheetQueue::pendingOrphans();

    $chunkUrlsCoveredByRoots = [];
    foreach ($rootConsumers as $rootConsumer) {
        foreach ($rootConsumer['chunks'] as $chunk) {
            $chunkUrlsCoveredByRoots[$chunk] = true;
        }
    }

    $orphanChunks = array_values(array_filter(
        $chunks,
        static fn (string $chunk): bool => ! isset($chunkUrlsCoveredByRoots[$chunk]),
    ));
@endphp

@if (count($stylesheets) > 0 || count($chunks) > 0)
    {{-- One CRG consumer per root component (form-field parity). Full stylesheetsFor() /
         alpineChunksFor() URL sets are emitted so deps like user-display stay retained with
         user-column — never share a single livewireKey across different URL sets. --}}
    @foreach ($rootConsumers as $rootConsumer)
        @include('filament-flex-fields::partials.emit-assets', [
            'stylesheets' => $rootConsumer['stylesheets'],
            'chunks' => $rootConsumer['chunks'],
            'consumerComponent' => $rootConsumer['component'],
            'livewireKey' => 'table-columns.'.$rootConsumer['component'],
        ])
    @endforeach

    {{-- Safety net for direct enqueue() without queueFor() roots. --}}
    @foreach ($orphanStylesheets as $orphanStylesheet)
        @include('filament-flex-fields::partials.emit-assets', [
            'stylesheets' => [$orphanStylesheet],
            'chunks' => [],
            'consumerComponent' => $orphanStylesheet,
            'livewireKey' => 'table-columns.'.$orphanStylesheet,
        ])
    @endforeach

    @foreach ($orphanChunks as $orphanChunk)
        @include('filament-flex-fields::partials.emit-assets', [
            'stylesheets' => [],
            'chunks' => [$orphanChunk],
            'consumerComponent' => $orphanChunk,
            'livewireKey' => 'table-columns.'.$orphanChunk,
        ])
    @endforeach

    @php
        FlexFieldStylesheetQueue::markStylesheetsEmitted($stylesheets);
        FlexFieldAlpineQueue::markChunksEmitted($chunks);
    @endphp
@endif
