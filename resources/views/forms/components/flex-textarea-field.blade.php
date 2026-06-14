@php
    use Filament\Support\Enums\IconSize;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $rows = $getRows();
    $placeholder = $getPlaceholder();
    $wrapperClasses = $getWrapperClasses();
    $initialHeight = $getInitialHeightRem();
    $footer = $getFooter();
    $toolbarActions = array_filter(
        $getPrefixActions(),
        fn (\Filament\Actions\Action|\Filament\Actions\ActionGroup $action): bool => $action->isVisible(),
    );
    $suffixActions = array_filter(
        $getSuffixActions(),
        fn (\Filament\Actions\Action $action): bool => $action->isVisible(),
    );
    $toolbarSelects = $getToolbarSelects();
    $shouldEnableSpeechDictation = $shouldEnableSpeechDictation() && ! $isDisabled && ! $isReadOnly;
    $shouldEnableEmojiPicker = $shouldEnableEmojiPicker() && ! $isDisabled && ! $isReadOnly;
    $initialState = \Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableHydrator::resolveRenderedState($field);

    $initialTextareaValue = is_scalar($initialState) ? (string) $initialState : '';
    $initialCharacterCount = mb_strlen($initialTextareaValue);
    $initialCharacterLimit = $getCharacterLimit();
    $initialCounterLabel = $shouldShowCharacterCounter()
        ? ($initialCharacterLimit
            ? "{$initialCharacterCount}/{$initialCharacterLimit}"
            : (string) $initialCharacterCount)
        : '';
    $isAutosizeEnabled = $shouldAutosize();
    $autosizeHeightStyle = $isAutosizeEnabled
        ? "min-height: {$initialHeight}rem;"
        : null;
    $hasToolbar = count($toolbarActions) > 0
        || count($toolbarSelects) > 0
        || count($suffixActions) > 0
        || $shouldShowCharacterCounter()
        || $shouldEnableSpeechDictation
        || $shouldEnableEmojiPicker;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'flex-textarea'])
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flex-textarea', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="flexTextareaFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            initialHeightRem: @js($initialHeight),
            shouldAutosize: @js($isAutosizeEnabled),
            animatedAutosize: @js($shouldAnimateAutosize()),
            maxHeight: @js($getMaxHeight()),
            characterLimit: @js($getCharacterLimit()),
            showCharacterCounter: @js($shouldShowCharacterCounter()),
            speechDictation: @js($shouldEnableSpeechDictation),
            speechDictationLanguage: @js($getSpeechDictationLanguage()),
            speechDictationLabel: @js($getSpeechDictationLabel()),
            speechDictationStopLabel: @js('Stop dictation'),
            emojiPicker: @js($shouldEnableEmojiPicker),
            emojiPickerLocale: @js($getEmojiPickerLocale()),
            emojiPickerLabel: @js($getEmojiPickerLabel()),
            initialState: @js(filled($initialTextareaValue) ? $initialTextareaValue : null),
            initialCharacterCount: @js($initialCharacterCount),
        })"
        x-init="init()"
        @class([
            'fff-flex-textarea',
            'fff-flex-textarea--'.$getSize(),
            'fff-flex-textarea--'.$getVariant(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-toolbar' => $hasToolbar,
            'has-counter' => $shouldShowCharacterCounter(),
            'has-dictation' => $shouldEnableSpeechDictation,
            'has-emoji-picker' => $shouldEnableEmojiPicker,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        @style([
            '--fff-flex-textarea-min-h' => $isAutosizeEnabled ? "{$initialHeight}rem" : null,
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div class="fff-flex-textarea__shell">
            <div wire:ignore.self class="fff-flex-textarea__content">
                <textarea
                    x-ref="textarea"
                    x-model="state"
                    @class([
                        'fff-flex-textarea__textarea',
                        'is-animated' => $shouldAnimateAutosize(),
                    ])
                    @if ($shouldAnimateAutosize())
                        x-on:input="resize()"
                    @endif
                    @if ($isGrammarlyDisabled())
                        data-gramm="false"
                        data-gramm_editor="false"
                        data-enable-grammarly="false"
                    @endif
                    {{
                        $getExtraInputAttributeBag()
                            ->merge([
                                'autocomplete' => $getAutocomplete(),
                                'autofocus' => $isAutofocused(),
                                'cols' => $getCols(),
                                'disabled' => $isDisabled,
                                'id' => $getId(),
                                'maxlength' => $getMaxLength(),
                                'minlength' => $getMinLength(),
                                'placeholder' => filled($placeholder) ? e($placeholder) : null,
                                'readonly' => $isReadOnly,
                                'required' => $isRequired(),
                                'rows' => $rows,
                                'style' => $autosizeHeightStyle,
                            ], escape: false)
                    }}>{{ filled($initialTextareaValue) ? e($initialTextareaValue) : '' }}</textarea>
            </div>

            @if ($hasToolbar)
                <div class="fff-flex-textarea__toolbar">
                    @if (count($toolbarActions) > 0 || count($toolbarSelects) > 0 || $shouldEnableEmojiPicker)
                        <div class="fff-flex-textarea__toolbar-start">
                            @if ($shouldEnableEmojiPicker)
                                @include('filament-flex-fields::forms.components.partials.flex-textarea-emoji-picker')
                            @endif

                            @foreach ($toolbarActions as $action)
                                {{ $action }}

                                @if ($loop->first)
                                    @foreach ($toolbarSelects as $select)
                                        @include('filament-flex-fields::forms.components.partials.flex-textarea-toolbar-select', [
                                            'select' => $select,
                                        ])
                                    @endforeach
                                @endif
                            @endforeach

                            @if (count($toolbarActions) === 0)
                                @foreach ($toolbarSelects as $select)
                                    @include('filament-flex-fields::forms.components.partials.flex-textarea-toolbar-select', ['select' => $select])
                                @endforeach
                            @endif
                        </div>
                    @endif

                    <div class="fff-flex-textarea__toolbar-end">
                        @if ($shouldShowCharacterCounter())
                            <span
                                class="fff-flex-textarea__counter"
                                x-bind:class="{
                                    'is-warning': characterLimit && characterCount >= characterLimit * 0.85,
                                    'is-danger': characterLimit && characterCount >= characterLimit,
                                }"
                                x-text="counterLabel"
                            >{{ $initialCounterLabel }}</span>
                        @endif

                        @if ($shouldEnableSpeechDictation)
                            <span
                                class="fff-flex-textarea__dictation-status"
                                x-show="dictationStatus"
                                x-cloak
                                x-text="dictationStatus"
                            ></span>

                            <button
                                type="button"
                                class="fff-flex-textarea__dictation-btn"
                                x-show="speechSupported"
                                x-cloak
                                x-bind:class="{ 'is-listening': isListening }"
                                x-bind:aria-pressed="isListening ? 'true' : 'false'"
                                x-bind:title="isListening ? speechDictationStopLabel : speechDictationLabel"
                                x-bind:aria-label="isListening ? speechDictationStopLabel : speechDictationLabel"
                                x-on:click="toggleDictation()"
                            >
                                {{ \Filament\Support\generate_icon_html($getMicrophoneIcon(), size: IconSize::Medium, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'fff-flex-textarea__dictation-icon'])) }}
                            </button>
                        @endif

                        @foreach ($suffixActions as $action)
                            {{ $action }}
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if (filled($footer))
            <p class="fff-flex-textarea__footer">{{ $footer }}</p>
        @endif
    </div>
</x-dynamic-component>
