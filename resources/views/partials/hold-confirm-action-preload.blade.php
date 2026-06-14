@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Filament\Support\Facades\FilamentAsset;
@endphp

<link
    rel="modulepreload"
    href="{{ FilamentAsset::getAlpineComponentSrc('hold-confirm-action', FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
/>
