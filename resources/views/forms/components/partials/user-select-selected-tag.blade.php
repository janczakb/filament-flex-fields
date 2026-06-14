@php
    $removable = $removable ?? true;
@endphp

<span
    class="fff-user-select__selected-tag fff-tags-field__tag"
    @if (filled($value ?? null))
        data-value="{{ $value }}"
    @endif
>
    <span class="fff-user-select__selected-tag-content">
        {!! $tagHtml !!}
    </span>

    @if ($removable)
        <button
            type="button"
            class="fff-tags-field__tag-remove fff-user-select__selected-tag-remove"
            aria-label="{{ __('Remove :name', ['name' => $name ?? $value ?? 'user']) }}"
            tabindex="-1"
        >
            @include('filament-flex-fields::forms.components.partials.tag-pill-remove-icon')
        </button>
    @endif
</span>
