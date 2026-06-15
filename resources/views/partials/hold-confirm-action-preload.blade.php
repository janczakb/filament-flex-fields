@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Filament\Support\Facades\FilamentAsset;
@endphp

@push('styles')
    <link
        rel="modulepreload"
        href="{{ FilamentAsset::getAlpineComponentSrc('hold-confirm-action', FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        data-navigate-track
    />
@endpush
