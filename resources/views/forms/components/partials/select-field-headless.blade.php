@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;
    use Illuminate\Support\Js;

    $headlessSearchClearIconHtml = \Filament\Support\generate_icon_html(GravityIcon::CircleXmarkFill, size: IconSize::Small)?->toHtml() ?? '';
    $headlessSmartCreateIconHtml = \Filament\Support\generate_icon_html(GravityIcon::Plus, size: IconSize::Small)?->toHtml() ?? '';
    $headlessComponentKey = $getKey();
    $headlessTriggerDir = $getExtraAttributeBag()->get('dir');
    $headlessInlineSearchSsrValue = ($isInlineSearch && $isSearchable && ! $isMultiple)
        ? ($isInitialTriggerPlaceholder ? '' : strip_tags($initialTriggerLabel))
        : null;
    $shouldDeferHeadlessAlpine = false;
    $showHeadlessStaticTriggerLabel = ! $isUserSelectField
        && ! ($isInlineSearch && $isSearchable && ! $isMultiple);
    $headlessTriggerHasClearableValue = $field->isClearable() && filled($state) && ! $isMultiple && ! $isDisabled;
    $headlessUsesInlineSearchControl = $isInlineSearch && $isSearchable;
    $headlessListboxId = $id.'-listbox';
    $headlessSearchId = $id.'-search';
    $headlessMenuDomId = $id.'-fff-headless-menu';
    $headlessAlpineConfig = $getHeadlessAlpineConfig();
@endphp

<div
    wire:ignore
    wire:key="{{ $livewireKey }}.headless.{{ substr(md5(serialize([
        $isDisabled,
        $isMultiple,
        $isSearchable,
        $getSize(),
        $getVariant(),
        $optionLayout,
        $usesRichOptionHtml,
    ])), 0, 64) }}"
    @class([
        'fff-select-field__shell',
        'fff-select-field__shell--headless',
        'fi-select-input',
    ])
>
    @include('filament-flex-fields::forms.components.partials.select-field.trigger-ssr')

    <x-filament-flex-fields::lazy-alpine-mount
        :eager="! $shouldDeferHeadlessAlpine"
        :mount-immediately="! $shouldDeferHeadlessAlpine"
        :mount-on-interaction="$shouldDeferHeadlessAlpine"
        :wrap-slot="false"
    >
    @if ($shouldDeferHeadlessAlpine)
        <template x-if="shouldMount">
            <div>
    @endif

        <div
            x-load
            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('select-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
            x-data="fffHeadlessSelectField({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            ...{{ Js::from($headlessAlpineConfig) }},
        })"
            x-init="init()"
            x-on:keydown.escape.stop="comboboxOpen && comboboxCloseMenu()"
            x-on:click.outside="if (isEventInsideHeadlessMenu($event)) { return }; comboboxCloseMenu()"
            @class([
                'fff-select-field__interactive',
            ])
            {{
                $attributes
                    ->except(['id'])
                    ->merge($getExtraAlpineAttributes(), escape: false)
            }}
        >
        @include('filament-flex-fields::forms.components.partials.select-field.trigger')

        @include('filament-flex-fields::forms.components.partials.select-field.menu-shell')
        </div>

    @if ($shouldDeferHeadlessAlpine)
            </div>
        </template>
    @endif
    </x-filament-flex-fields::lazy-alpine-mount>
</div>
