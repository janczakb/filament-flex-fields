@push('styles')
    <link
        rel="modulepreload"
        href="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('rich-editor', 'filament/forms') }}"
        data-navigate-track
    />
@endpush
