@php
    use Bjanczak\FilamentFlexFields\Data\SocialPlatform;
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Bjanczak\FilamentFlexFields\Support\SocialLinks\SocialLinksNormalizer;
    use Filament\Support\Facades\FilamentAsset;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $hasError = filled($statePath) && $errors->has($statePath);
    $livewireKey = $getLivewireKey();
    $config = $field->getAlpineConfiguration();
    $initialState = \Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableHydrator::resolveRenderedState($field);
    $initialLinks = SocialLinksNormalizer::normalize(is_array($initialState) ? $initialState : []);
    $platformDefinitions = collect($field->getPlatformDefinitions())->keyBy('value');
    $usedPlatforms = collect($initialLinks)->pluck('platform')->all();
    $availablePlatformCount = collect($field->getPlatformValues())
        ->filter(fn (string $platform): bool => ! in_array($platform, $usedPlatforms, true))
        ->count();
    $maxLinks = $field->getMaxLinks();
    $canAddMoreSsr = ! ($isDisabled || $isReadOnly)
        && ($maxLinks === null || count($initialLinks) < $maxLinks)
        && $availablePlatformCount > 0;
    $isReorderable = $field->isReorderable();

    $resolvePlatformLabel = function (string $platform) use ($platformDefinitions): string {
        return $platformDefinitions->get($platform)['label']
            ?? SocialPlatform::tryFrom($platform)?->label()
            ?? $platform;
    };

    $resolvePlatformPlaceholder = function (string $platform) use ($platformDefinitions): string {
        return $platformDefinitions->get($platform)['placeholder']
            ?? SocialPlatform::tryFrom($platform)?->placeholder()
            ?? 'https://';
    };

    $firstAvailablePlatform = collect($field->getPlatformValues())
        ->first(fn (string $platform): bool => ! in_array($platform, $usedPlatforms, true));

    $ssrTriggerLabel = $firstAvailablePlatform !== null
        ? $resolvePlatformLabel($firstAvailablePlatform)
        : $config['labels']['choosePlatform'];
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'social-links-field'])

    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $getVariant(), $field->getPlatformValues(), $isReorderable, $field->shouldAutoFormatUrls()])), 0, 64) }}"
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('social-links-field', FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="socialLinksFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            readOnly: @js($isDisabled || $isReadOnly),
            initialLinks: @js(array_values($initialLinks)),
            initialSelectedPlatform: @js($firstAvailablePlatform),
            initialShowValidationErrors: @js($hasError),
            ...@js($config),
        })"
        x-init="init()"
        x-on:click.outside="if ($refs.platformMenu?.contains($event.target)) { return }; closePlatformMenu()"
        x-on:keydown.escape.window="closePlatformMenu()"
        @class([
            'fff-social-links',
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
            'has-error' => $hasError,
            'is-reorderable' => $isReorderable,
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div class="fff-social-links__toolbar">
            <div class="fff-social-links__add-wrap" x-ref="addShell">
                <button
                    type="button"
                    class="fff-social-links__add-trigger"
                    x-ref="addTrigger"
                    x-on:click="togglePlatformMenu()"
                    x-on:keydown="onAddTriggerKeydown($event)"
                    x-bind:disabled="readOnly || ! canAddMore"
                    x-bind:aria-expanded="platformMenuOpen"
                    x-bind:aria-haspopup="listbox"
                    aria-controls="{{ $statePath }}__platform-menu"
                    x-bind:aria-label="labels.addPlatform"
                    @disabled(! $canAddMoreSsr)
                >
                    <span
                        class="fff-social-links__add-label"
                        x-text="selectedPlatformLabel || labels.choosePlatform"
                    >{{ $ssrTriggerLabel }}</span>
                    <x-filament::icon :icon="$config['icons']['chevron']" class="fff-social-links__add-chevron" x-bind:class="{ 'is-open': platformMenuOpen }" />
                </button>

                <button
                    type="button"
                    class="fff-social-links__add-btn"
                    x-on:click="confirmAddPlatform()"
                    x-bind:disabled="readOnly || ! selectedPlatform || ! canAddMore"
                    x-bind:aria-label="labels.add"
                    @disabled(! $canAddMoreSsr || $firstAvailablePlatform === null)
                >
                    <span x-text="labels.add">{{ $config['labels']['add'] }}</span>
                </button>
            </div>

            <template x-teleport="body">
                <div
                    id="{{ $statePath }}__platform-menu"
                    role="listbox"
                    class="fff-social-links__platform-menu fff-select-dropdown-panel fff-teleported-menu"
                    x-ref="platformMenu"
                    x-show="platformMenuOpen && ! readOnly"
                    x-cloak
                    x-bind:class="{ 'is-positioned': platformMenuReady, 'is-open': platformMenuOpen && platformMenuReady }"
                    x-bind:aria-activedescendant="platformMenuActiveDescendant()"
                    x-on:keydown="onPlatformMenuKeydown($event)"
                    x-on:mousedown.stop
                >
                    <div class="fff-social-links__platform-options">
                        <template x-for="(platform, platformIndex) in availablePlatforms" :key="platform.value">
                            <button
                                type="button"
                                role="option"
                                class="fff-social-links__platform-option"
                                x-bind:id="platformOptionId(platform.value)"
                                x-bind:class="{
                                    'is-active': selectedPlatform === platform.value,
                                    'is-highlighted': isPlatformHighlighted(platform.value),
                                }"
                                x-bind:aria-selected="selectedPlatform === platform.value"
                                x-on:mouseenter="platformMenuHighlightIndex = platformIndex; selectedPlatform = platform.value"
                                x-on:mousedown.prevent="selectPlatform(platform.value)"
                            >
                                <span class="fff-social-links__platform-option-icon" x-html="platformIconMarkup(platform.brand)"></span>
                                <span x-text="platform.label"></span>
                            </button>
                        </template>

                        <p
                            class="fff-social-links__platform-empty"
                            x-show="availablePlatforms.length === 0"
                            x-text="labels.maxReached"
                        ></p>
                    </div>
                </div>
            </template>
        </div>

        @if ($initialLinks === [])
            <p class="fff-social-links__empty fff-social-links__ssr-fallback">
                {{ $config['labels']['empty'] }}
            </p>
        @endif

        <p
            class="fff-social-links__empty fff-social-links__live-fallback"
            x-show="links.length === 0"
            x-text="labels.empty"
        ></p>

        <div @class(['fff-social-links__lists' => $initialLinks !== []])>
            @if ($initialLinks !== [])
                <ul class="fff-social-links__list fff-social-links__ssr-list" aria-hidden="true">
                    @foreach ($initialLinks as $link)
                        @php
                            $platform = (string) ($link['platform'] ?? '');
                            $url = (string) ($link['url'] ?? '');
                        @endphp
                        <li class="fff-social-links__item" data-platform="{{ $platform }}">
                            <div class="fff-social-links__item-head">
                                <span class="fff-social-links__item-icon">
                                    @include('filament-flex-fields::partials.social-platform-icon', ['platform' => $platform])
                                </span>
                                <span class="fff-social-links__item-label">{{ $resolvePlatformLabel($platform) }}</span>
                                <span class="fff-social-links__remove-btn fff-social-links__ssr-remove" aria-hidden="true">
                                    <x-filament::icon :icon="$config['icons']['remove']" class="h-4 w-4" />
                                </span>
                            </div>

                            <div class="fff-social-links__item-body">
                                <div @class(['fff-flex-text-input__shell', 'is-invalid' => $hasError])>
                                    <div class="fff-flex-text-input__row">
                                        <div class="fff-flex-text-input__control">
                                            <x-filament::input.wrapper
                                                :attributes="
                                                    \Filament\Support\prepare_inherited_attributes(new \Illuminate\View\ComponentAttributeBag())
                                                        ->class(['fff-flex-text-input__wrapper'])
                                                "
                                            >
                                                <input
                                                    type="url"
                                                    class="fff-flex-text-input__input fi-input"
                                                    value="{{ e($url) }}"
                                                    placeholder="{{ e($resolvePlatformPlaceholder($platform)) }}"
                                                    readonly
                                                    tabindex="-1"
                                                    aria-hidden="true"
                                                />
                                            </x-filament::input.wrapper>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <ul class="fff-social-links__list fff-social-links__live-list" role="list">
                <template x-for="(link, index) in links" :key="link.platform">
                    <li class="fff-social-links__item" x-bind:data-platform="link.platform">
                        <div class="fff-social-links__item-head">
                            <span class="fff-social-links__item-icon" x-html="platformIconMarkup(link.platform)"></span>
                            <span class="fff-social-links__item-label" x-text="platformLabel(link.platform)"></span>

                            <div class="fff-social-links__item-actions" x-show="reorderable">
                                <button
                                    type="button"
                                    class="fff-social-links__reorder-btn"
                                    x-on:click="moveLinkUp(index)"
                                    x-bind:disabled="! canMoveLinkUp(index)"
                                    x-bind:aria-label="`${labels.moveUp} ${platformLabel(link.platform)}`"
                                >
                                    <x-filament::icon :icon="$config['icons']['chevronUp']" class="h-4 w-4" />
                                </button>

                                <button
                                    type="button"
                                    class="fff-social-links__reorder-btn"
                                    x-on:click="moveLinkDown(index)"
                                    x-bind:disabled="! canMoveLinkDown(index)"
                                    x-bind:aria-label="`${labels.moveDown} ${platformLabel(link.platform)}`"
                                >
                                    <x-filament::icon :icon="$config['icons']['chevronDown']" class="h-4 w-4" />
                                </button>
                            </div>

                            <button
                                type="button"
                                class="fff-social-links__remove-btn"
                                x-on:click="removeLink(index)"
                                x-bind:disabled="readOnly"
                                x-bind:aria-label="`${labels.remove} ${platformLabel(link.platform)}`"
                            >
                                <x-filament::icon :icon="$config['icons']['remove']" class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="fff-social-links__item-body">
                            <div
                                class="fff-flex-text-input__shell"
                                x-bind:class="{ 'is-invalid': rowHasError(index) }"
                            >
                                <div class="fff-flex-text-input__row">
                                    <div class="fff-flex-text-input__control">
                                        <x-filament::input.wrapper
                                            :attributes="
                                                \Filament\Support\prepare_inherited_attributes(new \Illuminate\View\ComponentAttributeBag())
                                                    ->class(['fff-flex-text-input__wrapper'])
                                            "
                                        >
                                            <label class="sr-only" x-bind:for="`${statePath}-${link.platform}-url`" x-text="`${platformLabel(link.platform)} ${labels.url}`"></label>
                                            <input
                                                type="url"
                                                inputmode="url"
                                                autocomplete="url"
                                                class="fff-flex-text-input__input fi-input"
                                                x-bind:id="`${statePath}-${link.platform}-url`"
                                                x-model="link.url"
                                                x-bind:placeholder="platformPlaceholder(link.platform)"
                                                x-bind:disabled="readOnly"
                                                x-bind:readonly="readOnly"
                                                x-on:blur="formatUrlOnBlur(index)"
                                            />
                                        </x-filament::input.wrapper>
                                    </div>
                                </div>
                            </div>

                            <p
                                class="fff-social-links__row-error"
                                x-show="rowError(index)"
                                x-text="rowError(index)"
                                role="alert"
                            ></p>
                        </div>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</x-dynamic-component>
