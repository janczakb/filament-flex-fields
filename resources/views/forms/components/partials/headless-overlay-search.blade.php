{{--
    Shared teleported overlay search shell (SelectField, IconPicker, tags…).
    Bind Alpine refs/handlers on the host component.
--}}
@props([
    'queryModel' => 'searchQuery',
    'searchRef' => 'overlaySearchInput',
    'openKey' => 'panelOpen',
    'listboxId' => null,
    'keydownHandler' => 'onOverlaySearchKeydown',
    'inputHandler' => null,
    'disabledBinding' => 'readOnly',
    'placeholderBinding' => null,
    'placeholder' => null,
    'searchLabel' => 'Search',
    'activeIndexKey' => 'activeIconIndex',
    'optionIdPrefix' => null,
])

<div class="fi-select-input-search-ctn fff-teleported-menu__search-wrap fff-headless-overlay__search">
    <label class="sr-only">{{ $searchLabel }}</label>
    <input
        type="search"
        x-ref="{{ $searchRef }}"
        class="fi-input fi-select-input-search-input fff-teleported-menu__search"
        x-model="{{ $queryModel }}"
        @if ($inputHandler)
            x-on:input.debounce.300ms="{{ $inputHandler }}()"
        @endif
        x-on:keydown="{{ $keydownHandler }}($event)"
        x-bind:disabled="{{ $disabledBinding }}"
        @if ($placeholderBinding)
            x-bind:placeholder="{{ $placeholderBinding }}"
        @elseif ($placeholder)
            placeholder="{{ $placeholder }}"
        @endif
        autocomplete="off"
        role="combobox"
        aria-autocomplete="list"
        x-bind:aria-expanded="{{ $openKey }}"
        @if ($listboxId)
            x-bind:aria-controls="{{ $listboxId }}"
        @endif
        @if ($optionIdPrefix)
            {{-- Prefix is an Alpine expression (e.g. componentKey + '-option-'), not a static string. --}}
            x-bind:aria-activedescendant="{{ $activeIndexKey }} >= 0 ? {{ $optionIdPrefix }} + {{ $activeIndexKey }} : null"
        @endif
    />
</div>
