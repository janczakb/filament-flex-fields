{{--
    SSR tag pills + counter for TagsField — hidden once Alpine hydrates.
--}}
@php
    $tagCountLabel = (string) count($initialTags);

    if ($maxTags !== null) {
        $tagCountLabel = count($initialTags).'/'.$maxTags;
    }
@endphp

@if (count($initialTags) > 0)
    <div class="fff-tags-field__tags fff-tags-field__tags-ssr" aria-hidden="true">
        @foreach ($initialTags as $tag)
            <span @class([
                'fff-tags-field__tag',
                'is-reorderable' => $isReorderable,
            ])>
                <span class="fff-tags-field__tag-label">{{ $field->getTagDisplayLabel($tag) }}</span>
                <span class="fff-tags-field__tag-remove" aria-hidden="true">
                    @include('filament-flex-fields::forms.components.partials.tag-pill-remove-icon')
                </span>
            </span>
        @endforeach
    </div>
@endif

@if ($shouldShowTagCount)
    <div class="fff-tags-field__meta fff-tags-field__meta-ssr" aria-hidden="true">{{ $tagCountLabel }}</div>
@endif
