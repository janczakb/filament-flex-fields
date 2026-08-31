@props([
    'label' => null,
    'enabled' => true,
    'shortcut' => null,
])

<div
    @class([
        'fff-video-field__control-tip',
        'has-tooltips' => $enabled,
    ])
    @if ($enabled)
        x-on:mouseenter="showControlTooltip($event.currentTarget)"
        x-on:mouseleave="hideControlTooltip($event.currentTarget)"
        x-on:pointerdown="suppressControlTooltip($event.currentTarget)"
        x-on:focusin="showControlTooltip($event.currentTarget)"
        x-on:focusout="hideControlTooltip($event.currentTarget)"
    @endif
>
    {{ $slot }}

    @if ($enabled)
        <span class="fff-video-field__tooltip" role="tooltip">
            <span class="fff-video-field__tooltip-label">
                @isset($tooltip)
                    {{ $tooltip }}
                @else
                    {{ $label }}
                @endisset
            </span>
            @if ($shortcut)
                <kbd class="fff-video-field__tooltip-kbd">{{ $shortcut }}</kbd>
            @endif
        </span>
    @endif
</div>
