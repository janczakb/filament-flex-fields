@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Filament\Support\Facades\FilamentAsset;

    $alpineSrc = FilamentAsset::getAlpineComponentSrc('hold-confirm-action', FilamentFlexFieldsPlugin::PACKAGE_NAME);
    $duration = $action->getHoldConfirmDuration() ?? 2000;
    $releaseDuration = $action->getHoldConfirmReleaseDuration();
    $sweep = $action->getHoldConfirmSweep();
    $mountHandler = $action->getHoldConfirmMountHandler();
    $url = $action->getUrl();
@endphp

@include('filament-flex-fields::partials.load-stylesheet', ['component' => 'hold-confirm-action'])

<span
    class="fff-hold-confirm-action-host"
    data-sweep="{{ $sweep }}"
    x-load
    x-load-src="{{ $alpineSrc }}"
    x-data="holdConfirmActionFormComponent({
        duration: @js($duration),
        releaseDuration: @js($releaseDuration),
        sweep: @js($sweep),
        disabled: @js($action->isDisabled()),
    })"
    @if (filled($mountHandler))
        x-on:fff-hold-complete="$wire.{{ $mountHandler }}"
    @elseif (filled($url))
        x-on:fff-hold-complete="{{ $action->shouldOpenUrlInNewTab() ? 'window.open(' . json_encode($url) . ", '_blank')" : 'window.location.href = ' . json_encode($url) }}"
    @endif
>
    {!! $action->renderHoldConfirmTriggerHtml() !!}
</span>
