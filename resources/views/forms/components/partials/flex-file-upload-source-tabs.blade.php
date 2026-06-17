@php
    use Bjanczak\FilamentFlexFields\Enums\FileUploadSource;

    $uploadSources = $field->getEnabledUploadSources();
    $uploadSourceSize = $field->getSize();
    $uploadSourceVariant = $field->getUploadSourceTabsVariant();
    $uploadSourceColor = $field->getUploadSourceTabsColor();
    $defaultSource = FileUploadSource::File->value;
@endphp

<div
    @class([
        'fff-flex-file-upload__source-tabs',
        'fff-segment-control',
        'w-full',
        'fi-color-'.$uploadSourceColor => filled($uploadSourceColor),
    ])
    role="tablist"
    aria-label="{{ __('filament-flex-fields::default.file_upload.sources.tabs.file') }}"
>
    <div
        x-ref="sourceTrack"
        @class([
            'fff-segment-track',
            'fff-segment-track--'.$uploadSourceSize,
            'fff-segment-track--ghost' => $uploadSourceVariant === 'ghost',
        ])
        x-bind:class="{ 'is-animated': indicatorAnimated }"
    >
        <div
            x-ref="indicator"
            aria-hidden="true"
            @class([
                'fff-segment-indicator',
                'fff-segment-indicator--ghost' => $uploadSourceVariant === 'ghost',
            ])
            x-bind:class="{ 'is-animated': indicatorAnimated }"
            x-bind:style="indicatorStyle"
        ></div>

        @foreach ($uploadSources as $source)
            @php
                $sourceKey = $source->value;
                $isDefaultSource = $sourceKey === $defaultSource;
            @endphp

            <button
                type="button"
                role="tab"
                @class([
                    'fff-segment-item',
                    'fff-segment-item--'.$uploadSourceSize,
                ])
                data-segment-value="{{ $sourceKey }}"
                data-segment-selected="{{ $isDefaultSource ? 'true' : 'false' }}"
                x-bind:data-segment-selected="isSelected(@js($sourceKey)) ? 'true' : 'false'"
                x-bind:aria-selected="isSelected(@js($sourceKey)) ? 'true' : 'false'"
                aria-controls="{{ $id }}-source-{{ $sourceKey }}"
                id="{{ $id }}-source-{{ $sourceKey }}-trigger"
                x-on:click="select(@js($sourceKey))"
            >
                <x-filament::icon :icon="$field->getUploadSourceTabIcon($source)" />
                <span class="fff-segment-item__label">{{ $field->getUploadSourceTabLabel($source) }}</span>
            </button>
        @endforeach
    </div>
</div>
