@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\VerticalAlignment;
    use Filament\Support\View\Components\ToggleComponent;
    use Illuminate\Support\Arr;

    $statePath = $getStatePath();
    $variant = $getVariant();
    $layout = $getLayout();
    $labelPosition = $getLabelPosition();
    $size = $getSize();
    $onColor = $getEffectiveOnColor();
    $offColor = $getEffectiveOffColor();
    $onIcon = $getOnIcon();
    $offIcon = $getOffIcon();
    $hasIcons = filled($onIcon) || filled($offIcon);
    $description = $getDescription();
    $badge = $getBadge();
    $badgeColor = $getBadgeColor();
    $isDisabled = $isDisabled();
    $isRippleEnabled = $isRippleEnabled();
    $isCompact = $isCompact();
    $isInlineToggle = $isInlineToggle();
    $showsInlineFieldLabel = $showsInlineFieldLabel();
    $label = $getLabel();
    $onColorClasses = Arr::toCssClasses(\Filament\Support\get_component_color_classes(ToggleComponent::class, $onColor));
    $offColorClasses = Arr::toCssClasses(\Filament\Support\get_component_color_classes(ToggleComponent::class, $offColor));
    $onColorClassList = array_values(array_filter(explode(' ', $onColorClasses)));
    $offColorClassList = array_values(array_filter(explode(' ', $offColorClasses)));
    $toggleColorClassList = array_values(array_unique([...$onColorClassList, ...$offColorClassList]));
    $toggleColorClassBinding = implode(', ', array_map(
        fn (string $class): string => sprintf(
            '%s: Boolean(state) ? %s : %s',
            json_encode($class),
            in_array($class, $onColorClassList, true) ? 'true' : 'false',
            in_array($class, $offColorClassList, true) ? 'true' : 'false',
        ),
        $toggleColorClassList,
    ));
    $isChecked = (bool) $getState();
    $checkedAttribute = $isChecked ? 'true' : 'false';
    $switchControlAttributes = [
        'statePath' => $statePath,
        'label' => $label,
        'fieldId' => $getId(),
        'isDisabled' => $isDisabled,
        'isRippleEnabled' => $isRippleEnabled,
        'hasIcons' => $hasIcons,
        'onIcon' => $onIcon,
        'offIcon' => $offIcon,
        'onColorClasses' => $onColorClasses,
        'offColorClasses' => $offColorClasses,
        'toggleColorClassBinding' => $toggleColorClassBinding,
        'extraAlpineAttributes' => $getExtraAlpineAttributeBag(),
        'labelled' => $showsInlineFieldLabel,
        'isChecked' => $isChecked,
    ];
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :inline-label-vertical-alignment="VerticalAlignment::Center"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'switch'])
    @if ($isInlineToggle && $showsInlineFieldLabel)
        @if ($labelPosition === 'end')
            <x-slot name="labelSuffix">
                <div
                    x-data="{
                        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                        disabled: @js($isDisabled),
                        rippleEnabled: @js($isRippleEnabled),
                        toggle() {
                            if (this.disabled) {
                                return;
                            }

                            this.state = ! Boolean(this.state);
                        },
                        ripple(event) {
                            if (! this.rippleEnabled) {
                                return;
                            }

                            const track = event.currentTarget;
                            const circle = document.createElement('span');
                            const diameter = Math.max(track.clientWidth, track.clientHeight);

                            circle.className = 'fff-switch__ripple';
                            circle.style.width = `${diameter}px`;
                            circle.style.height = `${diameter}px`;
                            circle.style.left = `${event.offsetX - (diameter / 2)}px`;
                            circle.style.top = `${event.offsetY - (diameter / 2)}px`;

                            track.appendChild(circle);

                            window.setTimeout(() => circle.remove(), 650);
                        },
                    }"
                    @class([
                        'fff-switch',
                        'fff-switch--'.$size,
                        'fff-switch--inline',
                        'fff-switch--has-icons' => $hasIcons,
                        'fff-switch--ripple' => $isRippleEnabled,
                        'is-disabled' => $isDisabled,
                    ])
                >
                    @include('filament-flex-fields::forms.components.partials.switch-control', $switchControlAttributes)
                </div>
            </x-slot>
        @else
            <x-slot name="labelPrefix">
                <div
                    x-data="{
                        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                        disabled: @js($isDisabled),
                        rippleEnabled: @js($isRippleEnabled),
                        toggle() {
                            if (this.disabled) {
                                return;
                            }

                            this.state = ! Boolean(this.state);
                        },
                        ripple(event) {
                            if (! this.rippleEnabled) {
                                return;
                            }

                            const track = event.currentTarget;
                            const circle = document.createElement('span');
                            const diameter = Math.max(track.clientWidth, track.clientHeight);

                            circle.className = 'fff-switch__ripple';
                            circle.style.width = `${diameter}px`;
                            circle.style.height = `${diameter}px`;
                            circle.style.left = `${event.offsetX - (diameter / 2)}px`;
                            circle.style.top = `${event.offsetY - (diameter / 2)}px`;

                            track.appendChild(circle);

                            window.setTimeout(() => circle.remove(), 650);
                        },
                    }"
                    @class([
                        'fff-switch',
                        'fff-switch--'.$size,
                        'fff-switch--inline',
                        'fff-switch--has-icons' => $hasIcons,
                        'fff-switch--ripple' => $isRippleEnabled,
                        'is-disabled' => $isDisabled,
                    ])
                >
                    @include('filament-flex-fields::forms.components.partials.switch-control', $switchControlAttributes)
                </div>
            </x-slot>
        @endif
    @else
        <div
            x-data="{
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                disabled: @js($isDisabled),
                rippleEnabled: @js($isRippleEnabled),
                toggle() {
                    if (this.disabled) {
                        return;
                    }

                    this.state = ! Boolean(this.state);
                },
                ripple(event) {
                    if (! this.rippleEnabled) {
                        return;
                    }

                    const track = event.currentTarget;
                    const circle = document.createElement('span');
                    const diameter = Math.max(track.clientWidth, track.clientHeight);

                    circle.className = 'fff-switch__ripple';
                    circle.style.width = `${diameter}px`;
                    circle.style.height = `${diameter}px`;
                    circle.style.left = `${event.offsetX - (diameter / 2)}px`;
                    circle.style.top = `${event.offsetY - (diameter / 2)}px`;

                    track.appendChild(circle);

                    window.setTimeout(() => circle.remove(), 650);
                },
            }"
            @class([
                'fff-switch',
                'fff-switch--'.$size,
                'fff-switch--'.$variant => ! $isInlineToggle,
                'fff-switch--inline' => $isInlineToggle,
                'fff-switch--layout-'.$layout => ! $isInlineToggle,
                'fff-switch--label-'.$labelPosition => ! $isInlineToggle && $labelPosition === 'end',
                'fff-switch--has-description' => ! $isInlineToggle && filled($description),
                'fff-switch--has-icons' => $hasIcons,
                'fff-switch--compact' => $isCompact,
                'fff-switch--ripple' => $isRippleEnabled,
                'is-disabled' => $isDisabled,
            ])
            @unless ($showsInlineFieldLabel)
                role="group"
                aria-label="{{ $label }}"
            @endunless
        >
            @if ($isInlineToggle)
                @include('filament-flex-fields::forms.components.partials.switch-control', $switchControlAttributes)
            @else
                <button
                    type="button"
                    class="fff-switch__track"
                    role="switch"
                    aria-checked="{{ $checkedAttribute }}"
                    x-bind:aria-checked="Boolean(state) ? 'true' : 'false'"
                    x-bind:disabled="disabled"
                    x-on:click="ripple($event); toggle()"
                    wire:loading.attr="disabled"
                    wire:target="{{ $statePath }}"
                    {{ $getExtraAlpineAttributeBag() }}
                >
                    <span class="fff-switch__content">
                        <span class="fff-switch__title">
                            <span class="fff-switch__label">{{ $label }}</span>

                            @if (filled($badge))
                                <span @class([
                                    'fff-switch__badge',
                                    'fff-switch__badge--'.$badgeColor,
                                ])>{{ $badge }}</span>
                            @endif
                        </span>

                        @if (filled($description))
                            <span class="fff-switch__description">{{ $description }}</span>
                        @endif
                    </span>

                    <span
                        @class([
                            'fff-switch__control',
                            $isChecked ? $onColorClasses : $offColorClasses,
                        ])
                        data-checked="{{ $checkedAttribute }}"
                        x-bind:data-checked="Boolean(state) ? 'true' : 'false'"
                        x-bind:class="{ {{ $toggleColorClassBinding }} }"
                        aria-hidden="true"
                    >
                        <span @class([
                            'fff-switch__thumb',
                            'fff-switch__thumb--with-icons' => $hasIcons,
                        ])>
                            @if ($hasIcons)
                                <span class="fff-switch__thumb-icons">
                                    <span class="fff-switch__thumb-icon fff-switch__thumb-icon--off" aria-hidden="true">
                                        {{ \Filament\Support\generate_icon_html($offIcon, size: IconSize::ExtraSmall) }}
                                    </span>

                                    <span class="fff-switch__thumb-icon fff-switch__thumb-icon--on" aria-hidden="true">
                                        {{ \Filament\Support\generate_icon_html($onIcon, size: IconSize::ExtraSmall) }}
                                    </span>
                                </span>
                            @endif
                        </span>
                    </span>
                </button>
            @endif
        </div>
    @endif
</x-dynamic-component>
