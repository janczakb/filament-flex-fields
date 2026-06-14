@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\RawJs;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $datalistOptions = $getDatalistOptions();
    $extraAlpineAttributes = $getExtraAlpineAttributes();
    $isConcealed = $isConcealed();
    $isPasswordRevealable = $isPasswordRevealable();
    $isPrefixInline = $isPrefixInline();
    $isSuffixInline = $isSuffixInline();
    $mask = $getMask();
    $prefixActions = $getPrefixActions();
    $prefixIcon = $getPrefixIcon();
    $prefixIconColor = $getPrefixIconColor();
    $prefixLabel = $getPrefixLabel();
    $suffixActions = array_filter(
        $getSuffixActions(),
        fn (\Filament\Actions\Action $action): bool => $action->isVisible(),
    );
    $suffixIcon = $getSuffixIcon();
    $suffixIconColor = $getSuffixIconColor();
    $suffixLabel = $getSuffixLabel();
    $id = $getId();
    $placeholder = $getPlaceholder();
    $shouldEnableSpeechDictation = $shouldEnableSpeechDictation() && ! $isDisabled && ! $isReadOnly;
    $shouldEnableEmojiPicker = $shouldEnableEmojiPicker() && ! $isDisabled && ! $isReadOnly;
    $shouldShowCharacterCounter = $shouldShowCharacterCounter();
    $shouldShowClearable = $isClearable() && ! $isDisabled && ! $isReadOnly;
    $shouldShowLoadingIndicator = $shouldShowLoadingIndicator();
    $loadingWireTargets = $getLoadingWireTargets();
    $shouldShowPasswordStrength = $shouldShowPasswordStrength() && ! $isDisabled;
    $hasVerificationStatus = $hasVerificationStatus();
    $verificationStatus = $getVerificationStatus();
    $verificationStatusIcon = $getVerificationStatusIcon();
    $verificationStatusColor = $getVerificationStatusColor();
    $hasActionGroupItems = $shouldEnableSpeechDictation
        || $shouldEnableEmojiPicker
        || $shouldShowClearable
        || count($suffixActions) > 0;
    $hasLoadingOnlyActionGroup = $shouldShowLoadingIndicator && ! $hasActionGroupItems;
    $hasActionGroup = $hasActionGroupItems || $hasLoadingOnlyActionGroup;
    $hasPersistentActions = $shouldEnableSpeechDictation
        || $shouldEnableEmojiPicker
        || count($suffixActions) > 0;
    $loadingInGroup = $shouldShowLoadingIndicator && $hasActionGroupItems;
    $hasMeta = $shouldShowCharacterCounter || $shouldShowPasswordStrength;
    $initialState = \Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableHydrator::resolveRenderedState($field);

    $initialInputValue = (is_scalar($initialState) && filled($initialState))
        ? (string) $initialState
        : null;
    $initialCharacterCount = mb_strlen((string) ($initialState ?? ''));
    $initialCanClear = $shouldShowClearable && $initialCharacterCount > 0;
    $initialActionGroupVisible = $hasPersistentActions || $initialCanClear;
    $initialCharacterLimit = $getCharacterLimit();
    $initialCounterLabel = $shouldShowCharacterCounter
        ? ($initialCharacterLimit
            ? "{$initialCharacterCount}/{$initialCharacterLimit}"
            : (string) $initialCharacterCount)
        : '';
    $livewireKey = $getLivewireKey();

    if ($isPasswordRevealable) {
        $type = 'password';
    } elseif (filled($mask)) {
        $type = 'text';
    } else {
        $type = $getType();
    }

    $inputAttributes = $getExtraInputAttributeBag()
        ->merge($extraAlpineAttributes, escape: false)
        ->merge([
            'autocapitalize' => $getAutocapitalize(),
            'autocomplete' => $getAutocomplete(),
            'autofocus' => $isAutofocused(),
            'disabled' => $isDisabled,
            'id' => $id,
            'inlinePrefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
            'inlineSuffix' => $isSuffixInline && ($suffixIcon || filled($suffixLabel)),
            'inputmode' => $getInputMode(),
            'list' => $datalistOptions ? $id.'-list' : null,
            'max' => (! $isConcealed) ? $getMaxValue() : null,
            'maxlength' => (! $isConcealed) ? $getMaxLength() : null,
            'min' => (! $isConcealed) ? $getMinValue() : null,
            'minlength' => (! $isConcealed) ? $getMinLength() : null,
            'placeholder' => filled($placeholder) ? e($placeholder) : null,
            'readonly' => $isReadOnly,
            'required' => $isRequired() && (! $isConcealed),
            'step' => $getStep(),
            'type' => $type,
            'x-bind:type' => $isPasswordRevealable ? 'isPasswordRevealed ? \'text\' : \'password\'' : null,
            'x-mask'.($mask instanceof RawJs ? ':dynamic' : '') => filled($mask) ? $mask : null,
        ], escape: false);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :inline-label-vertical-alignment="\Filament\Support\Enums\VerticalAlignment::Center"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'flex-text-input'])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize()])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flex-text-input', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="flexTextInputFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            characterLimit: @js($getCharacterLimit()),
            showCharacterCounter: @js($shouldShowCharacterCounter),
            clearable: @js($shouldShowClearable),
            showPasswordStrength: @js($shouldShowPasswordStrength),
            passwordStrengthLabels: @js($getPasswordStrengthLabels()),
            speechDictation: @js($shouldEnableSpeechDictation),
            speechDictationLanguage: @js($getSpeechDictationLanguage()),
            speechDictationLabel: @js($getSpeechDictationLabel()),
            speechDictationStopLabel: @js('Stop dictation'),
            emojiPicker: @js($shouldEnableEmojiPicker),
            emojiPickerLocale: @js($getEmojiPickerLocale()),
            emojiPickerLabel: @js($getEmojiPickerLabel()),
            isPasswordRevealable: @js($isPasswordRevealable),
            hasPersistentActions: @js($hasPersistentActions),
            loadingInGroup: @js($loadingInGroup),
            initialCharacterCount: @js($initialCharacterCount),
            initialState: @js($initialInputValue ?? (is_scalar($initialState) ? (string) $initialState : null)),
        })"
        x-init="init()"
        @class([
            'fff-flex-text-input',
            'fff-flex-text-input--'.$getSize(),
            'fff-flex-text-input--'.$getVariant(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-actions' => $hasActionGroupItems,
            'has-inline-loading' => $hasLoadingOnlyActionGroup,
            'has-counter' => $shouldShowCharacterCounter,
            'has-meta' => $hasMeta,
            'has-password-strength' => $shouldShowPasswordStrength,
            'has-dictation' => $shouldEnableSpeechDictation,
            'has-emoji-picker' => $shouldEnableEmojiPicker,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div class="fff-flex-text-input__shell">
            <div class="fff-flex-text-input__row">
                <div class="fff-flex-text-input__control">
                    <x-filament::input.wrapper
                        :disabled="$isDisabled"
                        :inline-prefix="$isPrefixInline"
                        :inline-suffix="$isSuffixInline"
                        :prefix="$prefixLabel"
                        :prefix-actions="$prefixActions"
                        :prefix-icon="$prefixIcon"
                        :prefix-icon-color="$prefixIconColor"
                        :suffix="$suffixLabel"
                        :suffix-actions="[]"
                        :suffix-icon="$suffixIcon"
                        :suffix-icon-color="$suffixIconColor"
                        :valid="! $errors->has($statePath)"
                        x-on:focus-input.stop="focusInputFromAffix()"
                        :attributes="
                            \Filament\Support\prepare_inherited_attributes(new \Illuminate\View\ComponentAttributeBag())
                                ->class(['fff-flex-text-input__wrapper'])
                        "
                    >
                        <input
                            x-ref="input"
                            @if (filled($initialInputValue))
                                value="{{ e($initialInputValue) }}"
                            @endif
                            x-on:input="onInput($event)"
                            {{
                                $inputAttributes->class([
                                    'fff-flex-text-input__input',
                                    'fi-input',
                                    'fi-revealable' => $isPasswordRevealable,
                                    'fi-input-has-inline-prefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                                    'fi-input-has-inline-suffix' => $isSuffixInline && ($suffixIcon || filled($suffixLabel)),
                                ])
                            }}
                        />
                    </x-filament::input.wrapper>
                </div>

                @if ($hasActionGroupItems)
                    <div
                        class="fff-flex-text-input__action-group"
                        x-show="shouldShowActionGroup"
                        x-bind:class="{
                            'fff-flex-text-input__action-group--loading-only': loadingInGroup && loadingVisible && ! hasPersistentActions && ! (clearable && characterCount > 0),
                        }"
                        @unless ($initialActionGroupVisible) style="display: none;" @endunless
                    >
                        @if ($shouldEnableEmojiPicker)
                            @include('filament-flex-fields::forms.components.partials.flex-text-input-emoji-picker')
                        @endif

                        @if ($shouldEnableSpeechDictation)
                            <div class="fff-flex-text-input__action-item fff-flex-text-input__dictation">
                                <button
                                    type="button"
                                    class="fff-flex-text-input__action-btn fff-flex-text-input__action-btn--dictation"
                                    x-bind:class="{ 'is-listening': isListening }"
                                    x-bind:aria-pressed="isListening ? 'true' : 'false'"
                                    x-bind:title="isListening ? speechDictationStopLabel : speechDictationLabel"
                                    x-bind:aria-label="isListening ? speechDictationStopLabel : speechDictationLabel"
                                    x-on:click="toggleDictation()"
                                >
                                    {{ \Filament\Support\generate_icon_html($getMicrophoneIcon(), size: IconSize::Small, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'fff-flex-text-input__action-icon'])) }}
                                </button>
                            </div>
                        @endif

                        @if ($shouldShowClearable)
                            <div
                                class="fff-flex-text-input__action-item fff-flex-text-input__clear"
                                x-show="canClear"
                                @unless ($initialCanClear) style="display: none;" x-cloak @endunless
                            >
                                <button
                                    type="button"
                                    class="fff-flex-text-input__action-btn fff-flex-text-input__action-btn--clear"
                                    title="Clear"
                                    aria-label="Clear"
                                    x-on:click="clearInput()"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="fff-flex-text-input__action-icon">
                                        <path d="M18 6 6 18" />
                                        <path d="m6 6 12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endif

                        @if ($shouldShowLoadingIndicator)
                            <div
                                class="fff-flex-text-input__action-item fff-flex-text-input__loading"
                                wire:loading.delay.class="is-visible"
                                wire:target="{{ $loadingWireTargets }}"
                                aria-hidden="true"
                            >
                                <span class="fff-flex-text-input__loading-indicator">
                                    <x-filament::loading-indicator class="fff-flex-text-input__loading-spinner" />
                                </span>
                            </div>
                        @endif

                        @foreach ($suffixActions as $action)
                            @php
                                $suffixSlotAttributes = new \Illuminate\View\ComponentAttributeBag([
                                    'class' => 'fff-flex-text-input__action-item fff-flex-text-input__suffix-action',
                                ]);

                                if ($action->getName() === 'showPassword') {
                                    $suffixSlotAttributes = $suffixSlotAttributes->merge([
                                        'x-show' => '! isPasswordRevealed',
                                    ]);
                                } elseif ($action->getName() === 'hidePassword') {
                                    $suffixSlotAttributes = $suffixSlotAttributes->merge([
                                        'x-show' => 'isPasswordRevealed',
                                        'x-cloak' => true,
                                    ]);
                                }
                            @endphp
                            <div {{ $suffixSlotAttributes }}>
                                {{ $action }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($hasLoadingOnlyActionGroup)
                <div
                    class="fff-flex-text-input__loading-indicator fff-flex-text-input__loading-indicator--inline"
                    wire:loading.delay.class="is-visible"
                    wire:target="{{ $statePath }}"
                    aria-hidden="true"
                >
                    <x-filament::loading-indicator class="fff-flex-text-input__loading-spinner" />
                </div>
            @endif
        </div>

        @if ($hasMeta)
            <div class="fff-flex-text-input__meta">
                @if ($shouldShowCharacterCounter)
                    <span
                        class="fff-flex-text-input__counter"
                        x-bind:class="{
                            'is-warning': characterLimit && characterCount >= characterLimit * 0.85,
                            'is-danger': characterLimit && characterCount >= characterLimit,
                        }"
                        x-text="counterLabel"
                    >{{ $initialCounterLabel }}</span>
                @endif

                @if ($shouldShowPasswordStrength)
                    @php
                        $initialPasswordStrength = $field->calculatePasswordStrength((string) ($getState() ?? ''));
                    @endphp

                    <div
                        class="fff-flex-text-input__password-strength"
                        x-show="passwordStrengthMeta.score > 0"
                        @if ($initialPasswordStrength['score'] <= 0) style="display: none;" @endif
                    >
                        <div class="fff-flex-text-input__password-strength-track">
                            <span
                                class="fff-flex-text-input__password-strength-bar is-level-{{ $initialPasswordStrength['score'] }}"
                                x-bind:class="'is-level-' + passwordStrengthMeta.score"
                                style="width: {{ $initialPasswordStrength['percent'] }}%"
                                x-bind:style="{ width: passwordStrengthMeta.percent + '%' }"
                            ></span>
                        </div>
                        <span class="fff-flex-text-input__password-strength-label" x-text="passwordStrengthMeta.label">{{ $initialPasswordStrength['label'] }}</span>
                    </div>
                @endif
            </div>
        @endif

        @if ($shouldEnableSpeechDictation)
            <span
                class="fff-flex-text-input__dictation-status"
                x-show="dictationStatus"
                x-cloak
                x-text="dictationStatus"
            ></span>
        @endif

        @if ($hasVerificationStatus)
            <div @class([
                'fff-flex-text-input__verification-status',
                'fi-color-' . $verificationStatusColor,
            ])>
                <span class="fff-flex-text-input__verification-status-icon" aria-hidden="true">
                    {{ \Filament\Support\generate_icon_html($verificationStatusIcon, size: IconSize::Small) }}
                </span>
                <span class="fff-flex-text-input__verification-status-label">
                    {{ $verificationStatus }}
                </span>
            </div>
        @endif
    </div>

    @if ($datalistOptions)
        <datalist id="{{ $id }}-list">
            @foreach ($datalistOptions as $option)
                <option value="{{ $option }}"></option>
            @endforeach
        </datalist>
    @endif
</x-dynamic-component>
