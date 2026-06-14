@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Bjanczak\FilamentFlexFields\Support\Slug\SlugGenerator;
    use Filament\Support\Enums\IconSize;
    use Illuminate\View\ComponentAttributeBag;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $hasError = filled($statePath) && $errors->has($statePath);
    $livewireKey = $getLivewireKey();
    $titleField = $field->getConfiguredTitleField();
    $alpine = $field->getAlpineConfiguration();
    $componentKey = $getKey();
    $showPermalinkChrome = $alpine['permalinkPreview'];
    $slugReadOnly = $field->isSlugReadOnly();
    $hasSecondaryActionsConfig = $alpine['showRegenerateButton']
        || $alpine['showCopyButton']
        || $alpine['showVisitLink'];
    $hasActions = ! $isDisabled && ! $slugReadOnly && (
        $hasSecondaryActionsConfig
        || $alpine['inlineEditing']
    );
    $urlHost = $showPermalinkChrome && $field->isUrlHostVisible() ? $field->getUrlHost() : null;
    $urlHostDisplay = $showPermalinkChrome && $field->isUrlHostVisible() ? $field->getDisplayUrlHost() : null;
    $urlPath = $showPermalinkChrome && $field->isUrlPathVisible() ? $field->getUrlPath() : null;
    $urlPostfix = filled($field->getSlugLabelPostfix()) ? $field->getSlugLabelPostfix() : null;
    $showHttpsLock = filled($urlHost) && str_starts_with(strtolower((string) $urlHost), 'https');

    $initialSlug = $field->normalizeSlug(is_string($getState()) ? $getState() : '');
    $showInitialSlugSeparator = SlugGenerator::shouldShowPermalinkSlugSeparator($urlPath, $urlHost);
    $showInitialHomepageValue = $alpine['allowHomepage'] && $initialSlug === '/';
    $initialSlugDisplay = $showInitialHomepageValue
        ? ''
        : (filled($initialSlug) ? $initialSlug : ($alpine['placeholder'] ?? ''));
    $hasInitialFullUrl = filled($alpine['visitUrl']);
    $inlineEditing = (bool) $alpine['inlineEditing'];

    $showInitialEdit = $hasActions && $inlineEditing;
    $showInitialRegenerate = false;
    $showInitialCopy = $hasActions && $alpine['showCopyButton'] && $hasInitialFullUrl;
    $showInitialVisit = $hasActions && $alpine['showVisitLink'] && $alpine['canVisitLink'] && $hasInitialFullUrl;
    $showInitialSecondary = $hasActions && $hasSecondaryActionsConfig && (
        $showInitialRegenerate || $showInitialCopy || $showInitialVisit
    );
    $showInitialPreview = ! $slugReadOnly && ($inlineEditing || (! $inlineEditing && $showPermalinkChrome));
    $showInitialReadonlyPreview = $slugReadOnly;
    $showInitialStandalone = ! $inlineEditing && ! $showPermalinkChrome && ! $slugReadOnly;
    $showInitialDirectEditor = ! $inlineEditing && $showPermalinkChrome && ! $slugReadOnly;
    $showInitialBadge = filled($alpine['sourcePath']);
    $labels = $alpine['labels'];
    $initialAutoSyncDisabled = (bool) ($alpine['initialAutoSyncDisabled'] ?? false);
    $initialBadgeLabel = $initialAutoSyncDisabled ? $labels['custom'] : $labels['auto'];
    $initialBadgeClass = $initialAutoSyncDisabled ? 'is-custom' : 'is-auto';
    $showActionButtonLabels = (bool) ($alpine['showActionButtonLabels'] ?? true);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @if ($titleField)
        <div class="fff-slug-field__title">
            {{ $titleField }}
        </div>
    @endif

    <div class="fff-slug-field__host">
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'slug-field'])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $slugReadOnly, $getSize(), $field->getVariant(), $showPermalinkChrome])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('slug-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="slugFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            disabled: @js($isDisabled),
            componentKey: @js($componentKey),
            ...@js($alpine),
        })"
        x-init="init(); $el.classList.add('is-hydrated')"
        @class([
            'fff-slug-field',
            'fff-flex-text-input',
            'fff-slug-field--'.$getSize(),
            'fff-flex-text-input--'.$getSize(),
            'fff-slug-field--'.$field->getVariant(),
            'fff-flex-text-input--'.$field->getVariant(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly || $slugReadOnly,
            'has-permalink' => $showPermalinkChrome,
            'has-focus-outline' => $shouldShowFocusOutline(),
            'is-slug-readonly' => $slugReadOnly,
        ])
        x-bind:class="{
            'is-focused': isFocused,
            'is-editing': mode === 'edit',
            'is-customized': autoSyncDisabled,
        }"
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        @if ($showPermalinkChrome)
            <div class="fff-slug-field__header">
                <span class="fff-slug-field__eyebrow">{{ $labels['permalink'] }}</span>
                <div class="fff-slug-field__meta">
                    <span
                        class="fff-slug-field__badge {{ $initialBadgeClass }}"
                        @unless ($showInitialBadge) style="display: none;" x-cloak @endunless
                        x-show="sourcePath"
                        x-bind:class="autoSyncDisabled ? 'is-custom' : 'is-auto'"
                    >
                        <span
                            class="fff-slug-field__ssr-badge {{ $initialBadgeClass }}"
                            x-bind:class="{ 'is-replaced': displayReady }"
                        >{{ $initialBadgeLabel }}</span>
                        <span
                            class="fff-slug-field__live-badge"
                            x-bind:class="{ 'is-ready': displayReady }"
                            x-text="badgeLabel()"
                        ></span>
                    </span>
                    <span
                        class="fff-slug-field__changed"
                        style="display: none;"
                        x-cloak
                        x-show="isChangedFromRecord()"
                        x-text="labels.changed"
                    ></span>
                </div>
            </div>
        @endif

        <div @class([
            'fff-flex-text-input__shell fff-slug-field__shell',
            'is-invalid' => $hasError,
        ]) x-bind:class="{ 'is-invalid': uniqueError }">
            <div class="fff-flex-text-input__row fff-slug-field__row">
                <div class="fff-flex-text-input__control fff-slug-field__control">
                    <div class="fff-slug-field__permalink">
                        @if ($showHttpsLock)
                            <span class="fff-slug-field__secure" title="HTTPS" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html(
                                    GravityIcon::Lock,
                                    attributes: new ComponentAttributeBag(['class' => 'fff-slug-field__secure-icon']),
                                    size: IconSize::ExtraSmall,
                                ) }}
                            </span>
                        @endif

                        <div class="fff-slug-field__permalink-body">
                        <div
                            class="fff-slug-field__preview"
                            @unless ($showInitialPreview) style="display: none;" x-cloak @endunless
                            x-show="(showInlineEditor() && mode === 'view') || (! showInlineEditor() && permalinkPreview && ! slugReadOnly)"
                        >
                            @if ($showPermalinkChrome && (filled($urlHost) || filled($urlPath)))
                                <span class="fff-slug-field__prefix">
                                    @if (filled($urlHostDisplay)){{ $urlHostDisplay }}@endif@if (filled($urlPath)){{ $urlPath }}@endif
                                </span>
                            @endif

                            @if ($showInitialSlugSeparator)
                                <span
                                    class="fff-slug-field__slug-separator"
                                    aria-hidden="true"
                                >/</span>
                            @endif

                            <span class="fff-slug-field__value" x-text="displaySlug()">{{ $initialSlugDisplay }}</span>

                            @if (filled($urlPostfix))
                                <span class="fff-slug-field__postfix">{{ $urlPostfix }}</span>
                            @endif
                        </div>

                        <div
                            class="fff-slug-field__editor"
                            style="display: none;"
                            x-cloak
                            x-show="showInlineEditor() && mode === 'edit'"
                        >
                            @if ($showPermalinkChrome && (filled($urlHost) || filled($urlPath)))
                                <span class="fff-slug-field__prefix" aria-hidden="true">
                                    @if (filled($urlHostDisplay)){{ $urlHostDisplay }}@endif@if (filled($urlPath)){{ $urlPath }}@endif
                                </span>
                            @endif

                            @if ($showInitialSlugSeparator)
                                <span
                                    class="fff-slug-field__slug-separator"
                                    aria-hidden="true"
                                >/</span>
                            @endif

                            <input
                                type="text"
                                class="fff-slug-field__input fff-flex-text-input__input fi-input"
                                x-ref="slugInput"
                                x-model="draftSlug"
                                x-on:input="onDraftInput($event)"
                                x-on:focus="onFocus()"
                                x-on:blur="onBlur()"
                                x-on:keydown.enter.prevent="confirmEditing()"
                                x-on:keydown.escape.prevent="cancelEditing()"
                                spellcheck="false"
                                autocapitalize="off"
                                autocomplete="off"
                                inputmode="url"
                                aria-label="{{ $getLabel() }}"
                            />

                            @if (filled($urlPostfix))
                                <span class="fff-slug-field__postfix" aria-hidden="true">{{ $urlPostfix }}</span>
                            @endif
                        </div>

                        <input
                            type="text"
                            class="fff-slug-field__input fff-slug-field__input--standalone fff-flex-text-input__input fi-input"
                            @unless ($showInitialStandalone) style="display: none;" x-cloak @endunless
                            x-show="! showInlineEditor() && ! slugReadOnly && ! permalinkPreview"
                            x-bind:value="editableSlugValue()"
                            x-on:input="onDirectInput($event)"
                            x-on:focus="onFocus()"
                            x-on:blur="onBlur()"
                            x-bind:placeholder="placeholder"
                            x-bind:disabled="disabled"
                            spellcheck="false"
                            autocapitalize="off"
                            autocomplete="off"
                            inputmode="url"
                            aria-label="{{ $getLabel() }}"
                        />

                        <div
                            class="fff-slug-field__editor"
                            @unless ($showInitialDirectEditor) style="display: none;" x-cloak @endunless
                            x-show="! showInlineEditor() && permalinkPreview && ! slugReadOnly"
                        >
                            @if ($showPermalinkChrome && (filled($urlHost) || filled($urlPath)))
                                <span class="fff-slug-field__prefix" aria-hidden="true">
                                    @if (filled($urlHostDisplay)){{ $urlHostDisplay }}@endif@if (filled($urlPath)){{ $urlPath }}@endif
                                </span>
                            @endif

                            @if ($showInitialSlugSeparator)
                                <span
                                    class="fff-slug-field__slug-separator"
                                    aria-hidden="true"
                                >/</span>
                            @endif

                            <input
                                type="text"
                                class="fff-slug-field__input fff-flex-text-input__input fi-input"
                                x-bind:value="editableSlugValue()"
                                x-on:input="onDirectInput($event)"
                                x-on:focus="onFocus()"
                                x-on:blur="onBlur()"
                                x-bind:placeholder="placeholder"
                                x-bind:disabled="disabled"
                                spellcheck="false"
                                autocapitalize="off"
                                autocomplete="off"
                                inputmode="url"
                                aria-label="{{ $getLabel() }}"
                            />

                            @if (filled($urlPostfix))
                                <span class="fff-slug-field__postfix" aria-hidden="true">{{ $urlPostfix }}</span>
                            @endif
                        </div>

                        <div
                            class="fff-slug-field__preview is-readonly"
                            @unless ($showInitialReadonlyPreview) style="display: none;" x-cloak @endunless
                            x-show="slugReadOnly"
                        >
                            @if ($showPermalinkChrome && (filled($urlHost) || filled($urlPath)))
                                <span class="fff-slug-field__prefix">
                                    @if (filled($urlHostDisplay)){{ $urlHostDisplay }}@endif@if (filled($urlPath)){{ $urlPath }}@endif
                                </span>
                            @endif

                            @if ($showInitialSlugSeparator)
                                <span
                                    class="fff-slug-field__slug-separator"
                                    aria-hidden="true"
                                >/</span>
                            @endif

                            <span class="fff-slug-field__value" x-text="displaySlug()">{{ $initialSlugDisplay }}</span>
                            @if (filled($urlPostfix))
                                <span class="fff-slug-field__postfix">{{ $urlPostfix }}</span>
                            @endif
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($hasActions)
            <div class="fff-slug-field__actions" role="toolbar" aria-label="{{ $getLabel() }} actions">
                <div class="fff-slug-field__actions-primary">
                    <div
                        class="fff-slug-field__button-group fff-slug-field__button-group--horizontal"
                        role="group"
                        @unless ($showInitialEdit) style="display: none;" x-cloak @endunless
                        x-show="showInlineEditor() && mode === 'view'"
                    >
                        <button
                            type="button"
                            class="fff-slug-field__button fff-slug-field__button--md fff-slug-field__button--secondary"
                            x-on:click="startEditing()"
                            aria-label="{{ $labels['edit'] }}"
                            title="{{ $labels['edit'] }}"
                        >
                            {{ \Filament\Support\generate_icon_html(
                                GravityIcon::Pencil,
                                attributes: new ComponentAttributeBag(['class' => 'fff-slug-field__button-icon']),
                                size: IconSize::Small,
                            ) }}
                            <span class="fff-slug-field__button-label">{{ $labels['edit'] }}</span>
                        </button>
                    </div>

                    <div
                        class="fff-slug-field__button-group fff-slug-field__button-group--horizontal"
                        role="group"
                        style="display: none;"
                        x-cloak
                        x-show="showInlineEditor() && mode === 'edit'"
                    >
                        <button
                            type="button"
                            class="fff-slug-field__button fff-slug-field__button--md fff-slug-field__button--secondary"
                            x-on:click="confirmEditing()"
                            aria-label="{{ $labels['confirm'] }}"
                            title="{{ $labels['confirm'] }}"
                        >
                            {{ \Filament\Support\generate_icon_html(
                                GravityIcon::Check,
                                attributes: new ComponentAttributeBag(['class' => 'fff-slug-field__button-icon']),
                                size: IconSize::Small,
                            ) }}
                            <span class="fff-slug-field__button-label">{{ $labels['confirm'] }}</span>
                        </button>

                        <button
                            type="button"
                            class="fff-slug-field__button fff-slug-field__button--md fff-slug-field__button--secondary"
                            x-on:click="cancelEditing()"
                            aria-label="{{ $labels['cancel'] }}"
                            title="{{ $labels['cancel'] }}"
                        >
                            <span class="fff-slug-field__button-separator" aria-hidden="true"></span>
                            {{ \Filament\Support\generate_icon_html(
                                GravityIcon::Xmark,
                                attributes: new ComponentAttributeBag(['class' => 'fff-slug-field__button-icon']),
                                size: IconSize::Small,
                            ) }}
                            <span class="fff-slug-field__button-label">{{ $labels['cancel'] }}</span>
                        </button>
                    </div>
                </div>

                @if ($hasSecondaryActionsConfig)
                    <div
                        class="fff-slug-field__actions-secondary"
                        @unless ($showInitialSecondary) style="display: none;" x-cloak @endunless
                        x-show="hasSecondaryActions()"
                    >
                        <div class="fff-slug-field__button-group fff-slug-field__button-group--horizontal" role="group">
                        @if ($alpine['showRegenerateButton'])
                            <button
                                type="button"
                                @class([
                                    'fff-slug-field__button fff-slug-field__button--md fff-slug-field__button--secondary',
                                    'fff-slug-field__button--icon-only' => ! $showActionButtonLabels,
                                ])
                                style="display: none;"
                                x-cloak
                                x-show="canRegenerate()"
                                x-on:click="resetSlug()"
                                aria-label="{{ $labels['regenerate'] }}"
                                title="{{ $labels['regenerate'] }}"
                                @unless ($showActionButtonLabels)
                                    x-tooltip="{ content: @js($labels['regenerate']), theme: $store.theme }"
                                @endunless
                            >
                                {{ \Filament\Support\generate_icon_html(
                                    GravityIcon::ArrowRotateLeft,
                                    attributes: new ComponentAttributeBag(['class' => 'fff-slug-field__button-icon']),
                                    size: IconSize::Small,
                                ) }}
                                @if ($showActionButtonLabels)
                                    <span class="fff-slug-field__button-label">{{ $labels['regenerate'] }}</span>
                                @endif
                            </button>
                        @endif

                        @if ($alpine['showCopyButton'])
                            <button
                                type="button"
                                @class([
                                    'fff-slug-field__button fff-slug-field__button--md fff-slug-field__button--secondary',
                                    'fff-slug-field__button--icon-only' => ! $showActionButtonLabels,
                                ])
                                @unless ($showInitialCopy) style="display: none;" x-cloak @endunless
                                x-show="fullUrl()"
                                x-on:click="copyUrl()"
                                x-bind:disabled="! slug || ! fullUrl()"
                                x-bind:aria-label="copyFeedback ? labels.copied : labels.copy"
                                x-bind:title="copyFeedback ? labels.copied : labels.copy"
                                @unless ($showActionButtonLabels)
                                    x-tooltip="{ content: @js($labels['copy']), theme: $store.theme }"
                                @endunless
                            >
                                @if ($alpine['showRegenerateButton'])
                                    <span
                                        class="fff-slug-field__button-separator"
                                        aria-hidden="true"
                                        x-show="canRegenerate()"
                                    ></span>
                                @endif
                                <span x-show="! copyFeedback">
                                    {{ \Filament\Support\generate_icon_html(
                                        GravityIcon::Copy,
                                        attributes: new ComponentAttributeBag(['class' => 'fff-slug-field__button-icon']),
                                        size: IconSize::Small,
                                    ) }}
                                </span>
                                <span x-show="copyFeedback" x-cloak>
                                    {{ \Filament\Support\generate_icon_html(
                                        GravityIcon::Check,
                                        attributes: new ComponentAttributeBag(['class' => 'fff-slug-field__button-icon is-success']),
                                        size: IconSize::Small,
                                    ) }}
                                </span>
                                @if ($showActionButtonLabels)
                                    <span class="fff-slug-field__button-label" x-text="copyFeedback ? labels.copied : labels.copy">{{ $labels['copy'] }}</span>
                                @endif
                            </button>
                        @endif

                        @if ($alpine['showVisitLink'])
                            <button
                                type="button"
                                @class([
                                    'fff-slug-field__button fff-slug-field__button--md fff-slug-field__button--secondary',
                                    'fff-slug-field__button--icon-only' => ! $showActionButtonLabels,
                                ])
                                @unless ($showInitialVisit) style="display: none;" x-cloak @endunless
                                x-show="canVisitLink && fullUrl()"
                                x-on:click="openVisitUrl()"
                                x-bind:disabled="! canVisitLink || ! fullUrl()"
                                aria-label="{{ $labels['visit'] }}"
                                title="{{ $labels['visit'] }}"
                                @unless ($showActionButtonLabels)
                                    x-tooltip="{ content: @js($labels['visit']), theme: $store.theme }"
                                @endunless
                            >
                                @if ($alpine['showRegenerateButton'] || $alpine['showCopyButton'])
                                    <span
                                        class="fff-slug-field__button-separator"
                                        aria-hidden="true"
                                        x-show="canRegenerate() || ({{ $alpine['showCopyButton'] ? 'true' : 'false' }} && fullUrl())"
                                    ></span>
                                @endif
                                {{ \Filament\Support\generate_icon_html(
                                    GravityIcon::ArrowUpRightFromSquare,
                                    attributes: new ComponentAttributeBag(['class' => 'fff-slug-field__button-icon']),
                                    size: IconSize::Small,
                                ) }}
                                @if ($showActionButtonLabels)
                                    <span class="fff-slug-field__button-label">{{ $labels['visit'] }}</span>
                                @endif
                            </button>
                        @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <p
            class="fff-slug-field__unique-error"
            x-show="uniqueError"
            x-text="uniqueError"
            x-cloak
            role="alert"
        ></p>
    </div>
    </div>
</x-dynamic-component>
