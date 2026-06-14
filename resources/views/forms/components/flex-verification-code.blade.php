@php
    $statePath = $getStatePath();
    $length = $getLength();
    $groups = $getResolvedGroups();
    $separator = $getGroupSeparator();
    $showSeparators = $shouldShowSeparators();
    $allowedCharacters = $getAllowedCharacters();
    $inputMode = $getInputMode();
    $color = $getColor();
    $size = $getSize();
    $isDisabled = $isDisabled();
    $wrapperClasses = $getWrapperClasses();
    $initialState = $getState() ?? '';
    $shouldShowLoadingIndicator = $shouldShowLoadingIndicator();
    $loadingWireTargets = $getLoadingWireTargets();
    $autoSubmitEnabled = $shouldAutoSubmit();
    $autoSubmitMethod = $getAutoSubmitMethod();
    $autoSubmitUsesServerCallback = $shouldAutoSubmitUsingServerCallback();
    $hasError = filled($statePath) && $errors->has($statePath);
    $globalIndex = 0;
    $heading = $getHeading();
    $description = $getDescription();
    $footer = $getFooter();
    $footerAction = $getFooterAction();
    $hasLayoutChrome = $hasLayoutChrome();
    $accessibleLabel = filled($heading) ? $heading : $getLabel();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'flex-verification-code'])
    <div @class(['fff-verification-code-layout' => $hasLayoutChrome])>
        @if ($hasHeaderContent())
            <div class="fff-verification-code-layout__header">
                @if (filled($heading))
                    <div class="fff-verification-code-layout__heading">
                        {{ $heading }}
                    </div>
                @endif

                @if (filled($description))
                    <div class="fff-verification-code-layout__description">
                        {{ $description }}
                    </div>
                @endif
            </div>
        @endif

        <div
            @class([
                'fff-verification-code-shell',
                'fff-verification-code-shell--loading' => $shouldShowLoadingIndicator,
                'is-invalid' => $hasError,
            ])
            @if ($shouldShowLoadingIndicator || filled($autoSubmitMethod))
                wire:loading.class="is-loading"
                wire:target="{{ $loadingWireTargets }}"
            @endif
        >
            <div
                wire:ignore
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flex-verification-code', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
                x-data="flexVerificationCodeFormComponent({
                    state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) }},
                    length: @js($length),
                    allowedCharacters: @js($allowedCharacters),
                    disabled: @js($isDisabled),
                    autoSubmitEnabled: @js($autoSubmitEnabled),
                    autoSubmitMethod: @js($autoSubmitMethod),
                    autoSubmitUsesServerCallback: @js($autoSubmitUsesServerCallback),
                })"
                x-init="init()"
                @class($wrapperClasses)
                role="group"
                aria-label="{{ $accessibleLabel }}"
            >
            @foreach ($groups as $groupIndex => $groupSize)
                @if ($groupIndex > 0)
                    <span
                        @class([
                            'fff-verification-code__separator',
                            'fff-verification-code__separator--gap' => blank($separator),
                        ])
                        aria-hidden="true"
                    >
                        {{ $separator }}
                    </span>
                @endif

                <div class="fff-verification-code__group">
                    @for ($offset = 0; $offset < $groupSize; $offset++)
                        @php
                            $index = $globalIndex;
                            $initialChar = mb_substr($initialState, $index, 1);
                            $globalIndex++;
                        @endphp

                        <input
                            type="text"
                            maxlength="1"
                            inputmode="{{ $inputMode }}"
                            autocomplete="{{ $index === 0 ? 'one-time-code' : 'off' }}"
                            @class([
                                'fff-verification-code__input',
                                'is-filled' => filled($initialChar),
                            ])
                            aria-label="{{ $getDigitAriaLabel($index) }}"
                            @disabled($isDisabled)
                            value="{{ $initialChar }}"
                            x-ref="input{{ $index }}"
                            x-on:input="handleInput({{ $index }}, $event)"
                            x-on:keydown="handleKeydown({{ $index }}, $event)"
                            x-on:focus="handleFocus({{ $index }}, $event)"
                            x-on:paste="handlePaste($event)"
                            x-bind:class="{ 'is-filled': Boolean(chars[{{ $index }}]) }"
                            x-bind:disabled="disabled || autoSubmitPending"
                        />
                    @endfor
                </div>
            @endforeach
        </div>

        @if ($shouldShowLoadingIndicator)
            <div
                class="fff-verification-code__loading"
                wire:loading.delay.class="is-visible"
                wire:target="{{ $loadingWireTargets }}"
                aria-hidden="true"
            >
                <x-filament::loading-indicator class="fff-verification-code__loading-spinner" />
            </div>
        @endif
        </div>

        @if ($hasFooterContent())
            <div class="fff-verification-code-layout__footer">
                @if (filled($footer))
                    <span class="fff-verification-code-layout__footer-text">{{ $footer }}</span>
                @endif

                @if ($footerAction)
                    <span class="fff-verification-code-layout__footer-action fi-fixed-positioning-context">
                        {{ $footerAction }}
                    </span>
                @endif
            </div>
        @endif
    </div>
</x-dynamic-component>
