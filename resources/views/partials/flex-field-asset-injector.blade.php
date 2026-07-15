@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
    use Filament\Support\Facades\FilamentAsset;

    $src = FilamentAsset::getScriptSrc(
        FlexFieldAssets::ASSET_INJECTOR_SCRIPT_ID,
        FilamentFlexFieldsPlugin::PACKAGE_NAME,
    );

    $packageRoot = dirname((new \ReflectionClass(FilamentFlexFieldsPlugin::class))->getFileName(), 2);
    $versionFile = $packageRoot.'/VERSION';
    $distFile = $packageRoot.'/resources/dist/core/flex-field-asset-injector.js';
    $bust = trim((string) (@file_get_contents($versionFile) ?: '0'));
    $mtime = is_file($distFile) ? (string) filemtime($distFile) : '0';
    $separator = str_contains($src, '?') ? '&' : '?';
    $src .= $separator.'fff='.urlencode($bust.'.'.$mtime);
@endphp

<script
    src="{{ $src }}"
    data-navigate-track
></script>
