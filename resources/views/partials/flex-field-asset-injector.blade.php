@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Filament\Support\Facades\FilamentAsset;
@endphp

<script
    src="{{ FilamentAsset::getScriptSrc('flex-field-asset-injector', FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
    data-navigate-track
></script>
