{{--
    Blocking inline IIFE (same pattern as segment-overflow-shell): paints the
    official catalog label during HTML parse — before Alpine x-load. Catalog
    keys are the allowed IANA ids so the trigger never swaps Intl → package names.
--}}
@if ($shouldUseBrowserTimezoneDefault() && blank($selectedId))
    @php
        $timezoneBootId = 'fff-tz-boot-'.substr(md5($livewireKey.'|'.$statePath.'|browser'), 0, 16);
        $timezoneBootInline = \Bjanczak\FilamentFlexFields\Support\FlexFieldAssets::timezoneBrowserSsrInlineContents();
        $timezoneBootCatalog = $field->getBrowserTimezoneBootCatalog();
    @endphp
    <div
        id="{{ $timezoneBootId }}"
        class="fff-timezone-field__browser-boot"
        hidden
        data-fff-timezone-boot="1"
        data-fff-timezone-catalog="{{ json_encode($timezoneBootCatalog, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
    ></div>
    @if (filled($timezoneBootInline))
        <script>{!! $timezoneBootInline !!}</script>
    @endif
@endif
