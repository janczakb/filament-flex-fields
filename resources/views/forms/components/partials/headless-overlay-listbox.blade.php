{{--
    Shared teleported overlay listbox scroll shell (M2 keyboard + virtual list contract).
--}}
@props([
    'listRef' => 'overlayOptionsList',
    'openKey' => 'panelOpen',
    'scrollHandler' => 'onOverlayOptionsScroll',
    'keydownHandler' => 'onOverlayOptionsKeydown',
    'listboxId' => null,
    'extraClass' => '',
])

<div
    x-ref="{{ $listRef }}"
    class="fff-headless-overlay__list fi-select-input-options-ctn {{ $extraClass }}"
    x-on:scroll.passive="{{ $scrollHandler }}($event)"
    x-on:keydown="{{ $keydownHandler }}($event)"
    tabindex="-1"
    role="listbox"
    @if ($listboxId)
        x-bind:id="{{ $listboxId }}"
    @endif
    x-bind:aria-hidden="! {{ $openKey }}"
>
    {{ $slot }}
</div>
