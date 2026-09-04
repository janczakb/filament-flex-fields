@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAlpineQueue;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;

    $stylesheets = FlexFieldStylesheetQueue::pending();
    $chunks = FlexFieldAlpineQueue::pending();
    $tableConsumerComponents = array_values(array_unique($stylesheets));
@endphp

@if (count($stylesheets) > 0 || count($chunks) > 0)
    @foreach ($tableConsumerComponents as $tableConsumerComponent)
        @include('filament-flex-fields::partials.emit-assets', [
            'stylesheets' => [$tableConsumerComponent],
            'chunks' => [],
            'consumerComponent' => $tableConsumerComponent,
            'livewireKey' => 'table-columns',
        ])
    @endforeach

    @if (count($chunks) > 0)
        @include('filament-flex-fields::partials.emit-assets', [
            'stylesheets' => [],
            'chunks' => $chunks,
        ])
    @endif

    @php
        FlexFieldStylesheetQueue::markStylesheetsEmitted($stylesheets);
        FlexFieldAlpineQueue::markChunksEmitted($chunks);
    @endphp
@endif
