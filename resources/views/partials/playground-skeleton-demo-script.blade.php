@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
    use Filament\Support\Facades\FilamentAsset;
@endphp

<script
    src="{{ FilamentAsset::getScriptSrc(FlexFieldAssets::PLAYGROUND_SKELETON_DEMO_SCRIPT_ID, FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
    data-navigate-track
></script>
