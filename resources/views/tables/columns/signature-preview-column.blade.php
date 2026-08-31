<span
    @class([
        'fff-signature-preview-column',
        'fff-signature-preview-column--' . $size,
        'is-empty' => blank($svg),
    ])
>
    @if (filled($svg))
        <span class="fff-signature-preview-column__frame" aria-hidden="true">
            {!! $svg !!}
        </span>
    @else
        <span class="fff-signature-preview-column__empty" aria-hidden="true"></span>
    @endif
</span>
