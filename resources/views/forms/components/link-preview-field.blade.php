@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Facades\FilamentAsset;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $previewLayout = $getPreviewLayout();
    $hasError = filled($statePath) && $errors->has($statePath);
    $prefixLabel = $getPrefix();
    $suffixLabel = $getSuffix();
    $visitIcon = $getVisitIcon() ?? GravityIcon::Paperclip;
    $initialState = \Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableHydrator::resolveRenderedState($field);
    $initialUrl = is_string($initialState) && filled($initialState) ? $initialState : '';
    $livewireKey = $getLivewireKey();
    $config = $field->getAlpineConfiguration();
    $config['initialPreview'] = $field->shouldResolveInitialPreviewOnServer()
        ? $field->resolveInitialPreview($initialUrl !== '' ? $initialUrl : null)
        : null;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'link-preview-field'])

    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $getVariant(), $previewLayout])), 0, 64) }}"
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('link-preview-field', FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="linkPreviewFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            initialUrl: @js($initialUrl),
            disabled: @js($isDisabled),
            readOnly: @js($isReadOnly),
            ...@js($config),
        })"
        x-init="init()"
        @class([
            'fff-link-preview',
            'fff-flex-text-input',
            'fff-link-preview--'.$getSize(),
            'fff-flex-text-input--'.$getSize(),
            'fff-link-preview--'.$getVariant(),
            'fff-flex-text-input--'.$getVariant(),
            'fff-link-preview--layout-'.$previewLayout,
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div @class([
            'fff-flex-text-input__shell',
            'is-invalid' => $hasError,
        ])>
            <div class="fff-flex-text-input__row">
                <div class="fff-flex-text-input__control">
                    <x-filament::input.wrapper
                        :disabled="$isDisabled"
                        :inline-prefix="filled($prefixLabel)"
                        :inline-suffix="filled($suffixLabel)"
                        :prefix="$prefixLabel"
                        :suffix="$suffixLabel"
                        :valid="! $hasError"
                        :attributes="
                            \Filament\Support\prepare_inherited_attributes(new \Illuminate\View\ComponentAttributeBag())
                                ->class(['fff-flex-text-input__wrapper'])
                        "
                    >
                        <input
                            type="url"
                            inputmode="url"
                            autocomplete="url"
                            class="fff-flex-text-input__input fi-input"
                            @class([
                                'fi-input-has-inline-prefix' => filled($prefixLabel),
                                'fi-input-has-inline-suffix' => filled($suffixLabel),
                            ])
                            placeholder="{{ $getPlaceholder() }}"
                            x-ref="input"
                            x-model="inputValue"
                            x-on:input="onInput()"
                            x-on:blur="onBlur()"
                            @disabled($isDisabled)
                            @readonly($isReadOnly)
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>

        <div
            class="fff-link-preview__card"
            x-show="shouldShowCard"
            x-cloak
            aria-live="polite"
            x-bind:class="{
                'fff-link-preview__card--horizontal': previewLayout === 'horizontal',
                'fff-link-preview__card--vertical': previewLayout === 'vertical',
                'fff-link-preview__card--card': previewLayout === 'card',
                'fff-link-preview__card--no-thumb': ! shouldShowThumb,
                'is-loading': showSkeleton,
                'is-revealed': isRevealed,
            }"
            x-bind:aria-busy="showSkeleton ? 'true' : 'false'"
            x-bind:aria-label="isRevealed && preview.title ? preview.title : null"
        >
            <div
                class="fff-link-preview__thumb"
                x-show="shouldShowThumb"
                x-cloak
                aria-hidden="true"
            >
                <div class="fff-link-preview__skeleton-thumb" x-show="showSkeleton"></div>

                <img
                    class="fff-link-preview__image"
                    x-show="isRevealed && preview.image"
                    :src="preview.image"
                    alt=""
                    decoding="async"
                    x-on:error="onImageError()"
                >
            </div>

            <div class="fff-link-preview__content">
                <div class="fff-link-preview__skeleton" x-show="showSkeleton" aria-hidden="true">
                    <span class="fff-link-preview__skeleton-line fff-link-preview__skeleton-line--title"></span>
                    <span
                        class="fff-link-preview__skeleton-line fff-link-preview__skeleton-line--description"
                        x-show="previewLayout === 'horizontal'"
                    ></span>
                    <span class="fff-link-preview__skeleton-line fff-link-preview__skeleton-line--domain"></span>
                </div>

                <div class="fff-link-preview__meta" x-show="isRevealed" x-cloak>
                    <p
                        class="fff-link-preview__title"
                        x-show="preview.title"
                        x-text="preview.title"
                    ></p>

                    <p
                        class="fff-link-preview__description"
                        x-show="previewLayout === 'horizontal' && preview.description"
                        x-text="preview.description"
                    ></p>

                    <a
                        class="fff-link-preview__domain"
                        x-show="showVisitLink && preview.domain && hasPreview && canVisit"
                        :href="visitUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        x-bind:aria-label="labels.visit"
                    >
                        <span
                            class="fff-link-preview__domain-icon"
                            aria-hidden="true"
                        >
                            {{ \Filament\Support\generate_icon_html($visitIcon, size: IconSize::ExtraSmall) }}
                        </span>
                        <span x-text="preview.domain"></span>
                    </a>

                    <span
                        class="fff-link-preview__domain fff-link-preview__domain--text"
                        x-show="! showVisitLink && preview.domain && hasPreview"
                    >
                        <span
                            class="fff-link-preview__domain-icon"
                            aria-hidden="true"
                        >
                            {{ \Filament\Support\generate_icon_html($visitIcon, size: IconSize::ExtraSmall) }}
                        </span>
                        <span x-text="preview.domain"></span>
                    </span>
                </div>
            </div>
        </div>

        <p
            class="fff-link-preview__error"
            x-show="error"
            x-cloak
            x-text="error"
            role="alert"
        ></p>
    </div>
</x-dynamic-component>
