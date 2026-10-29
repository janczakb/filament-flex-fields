<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Support\Assets\ComponentAssetPlan;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput\Actions\CopyAction;
use Filament\Support\Icons\Heroicon;

it('defaults suggestion descriptions below and resolves per-field trailing configuration', function () {
    $default = FlexTextInput::make('default');
    $trailing = FlexTextInput::make('trailing')->suggestionDescriptionPosition(fn (): string => 'trailing');

    expect($default->getTextInputEditingOptions()['suggestionDescriptionPosition'])->toBe('below')
        ->and($trailing->getTextInputEditingOptions()['suggestionDescriptionPosition'])->toBe('trailing')
        ->and($default->getTextInputEditingOptions()['suggestionDescriptionPosition'])->toBe('below');
});

it('rejects unsupported suggestion description positions', function () {
    expect(fn () => FlexTextInput::make('city')->suggestionDescriptionPosition('sideways')->getTextInputEditingOptions())
        ->toThrow(InvalidArgumentException::class);
});

it('extends filament text input and exposes styling api', function () {
    $field = FlexTextInput::make('title')
        ->placeholder('Enter title')
        ->maxLength(120)
        ->email()
        ->prefixIcon(Heroicon::Envelope)
        ->suffix('.com')
        ->size('lg')
        ->variant('secondary')
        ->speechDictation()
        ->speechDictationLabel('Speak')
        ->emojiPicker()
        ->emojiPickerLabel('Emoji');

    expect($field)->toBeInstanceOf(TextInput::class)
        ->and($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('secondary')
        ->and($field->isPrefixInline())->toBeTrue()
        ->and($field->isSuffixInline())->toBeTrue()
        ->and($field->shouldEnableSpeechDictation())->toBeTrue()
        ->and($field->getSpeechDictationLabel())->toBe('Speak')
        ->and($field->shouldEnableEmojiPicker())->toBeTrue()
        ->and($field->getEmojiPickerLabel())->toBe('Emoji')
        ->and($field->isEmail())->toBeTrue();
});

it('uses native filament input types for email url tel password and numeric', function () {
    expect(FlexTextInput::make('email')->email()->getType())->toBe('email')
        ->and(FlexTextInput::make('website')->url()->getType())->toBe('url')
        ->and(FlexTextInput::make('phone')->tel()->getType())->toBe('tel')
        ->and(FlexTextInput::make('secret')->password()->getType())->toBe('password')
        ->and(FlexTextInput::make('amount')->numeric()->getType())->toBe('number')
        ->and(FlexTextInput::make('qty')->integer()->getType())->toBe('number')
        ->and(FlexTextInput::make('amount')->numeric()->getInputMode())->toBe('decimal')
        ->and(FlexTextInput::make('qty')->integer()->getInputMode())->toBe('numeric')
        ->and(FlexTextInput::make('qty')->integer()->getStep())->toBe(1);
});

it('exposes focus outline api', function () {
    expect(FlexTextInput::make('title')->shouldShowFocusOutline())->toBeTrue()
        ->and(FlexTextInput::make('title')->focusOutline()->shouldShowFocusOutline())->toBeTrue()
        ->and(FlexTextInput::make('title')->focusOutline(false)->shouldShowFocusOutline())->toBeFalse();
});

it('renders has-focus-outline on the flex text input root by default', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-text-input-field.blade.php');

    expect($blade)->toContain("'has-focus-outline' => \$shouldShowFocusOutline()")
        ->and(FlexTextInput::make('title')->shouldShowFocusOutline())->toBeTrue();
});

it('applies rounding class on both field wrapper and alpine root', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-text-input-field.blade.php');
    $field = FlexTextInput::make('title')->rounding('full');

    expect($field->getWrapperClasses())->toContain('fff-rounding-full')
        ->and($blade)->toContain("'fff-rounding-'.\$getRounding()");
});

it('rejects unsupported flex text input variants', function () {
    FlexTextInput::make('title')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('supports soft variant for gray shell without shadow', function () {
    $field = FlexTextInput::make('comment')->variant('soft');

    expect($field->getVariant())->toBe('soft')
        ->and($field->getWrapperClasses())->toContain('fff-flex-text-input-field--soft');
});

it('includes wrapper classes for size and variant', function () {
    $field = FlexTextInput::make('title')
        ->size('sm')
        ->variant('flat');

    expect($field->getWrapperClasses())->toBe([
        'fff-flex-text-input-field',
        'fff-flex-text-input-field--sm',
        'fff-rounding-md',
        'fff-flex-text-input-field--flat',
    ]);
});

it('does not enable speech dictation or emoji picker by default', function () {
    $field = FlexTextInput::make('title');

    expect($field->shouldEnableSpeechDictation())->toBeFalse()
        ->and($field->shouldEnableEmojiPicker())->toBeFalse();
});

it('supports native text input configuration methods', function () {
    $passwordField = FlexTextInput::make('token')
        ->password()
        ->revealable()
        ->copyable();

    expect($passwordField->isPassword())->toBeTrue()
        ->and($passwordField->isPasswordRevealable())->toBeTrue()
        ->and($passwordField->isCopyable())->toBeTrue();

    $numericField = FlexTextInput::make('amount')
        ->numeric()
        ->minValue(1)
        ->maxValue(100)
        ->step(5);

    expect($numericField->isNumeric())->toBeTrue()
        ->and($numericField->getMinValue())->toBe(1)
        ->and($numericField->getMaxValue())->toBe(100)
        ->and($numericField->getStep())->toBe(5);

    expect(FlexTextInput::make('site')->url()->isUrl())->toBeTrue()
        ->and(FlexTextInput::make('phone')->tel()->isTel())->toBeTrue();
});

it('uses Hugeicons for copy and password suffix actions by default', function () {
    $field = FlexTextInput::make('token')
        ->password()
        ->revealable()
        ->copyable();

    $icons = collect($field->getSuffixActions())
        ->mapWithKeys(fn ($action) => [$action->getName() => $action->getIcon()])
        ->all();

    expect($icons['copy'])->toBe(GravityIcon::Copy)
        ->and($icons['showPassword'])->toBe('hugeicons-view')
        ->and($icons['hidePassword'])->toBe('hugeicons-view-off');
});

it('uses native copy button feedback icon instead of filament tooltip handler', function () {
    $field = FlexTextInput::make('token')->copyable();

    $copy = collect($field->getSuffixActions())
        ->first(fn ($action) => $action->getName() === 'copy');

    expect($copy)->not->toBeNull();

    expect($copy)->toBeInstanceOf(CopyAction::class)
        ->and($field->getCopySuccessIcon())->toBe(GravityIcon::Check)
        ->and($field->getCopyFeedbackDurationMs())->toBe(2500);
});

it('allows overriding built-in flex text input action icons', function () {
    $field = FlexTextInput::make('token')
        ->password()
        ->revealable()
        ->copyable()
        ->copyIcon(Heroicon::OutlinedClipboardDocumentList)
        ->showPasswordIcon(Heroicon::OutlinedEye)
        ->hidePasswordIcon(Heroicon::OutlinedEyeSlash)
        ->emojiIcon(GravityIcon::FaceSmile)
        ->microphoneIcon(GravityIcon::Microphone);

    $icons = collect($field->getSuffixActions())
        ->mapWithKeys(fn ($action) => [$action->getName() => $action->getIcon()])
        ->all();

    expect($icons['copy'])->toBe(Heroicon::OutlinedClipboardDocumentList)
        ->and($icons['showPassword'])->toBe(Heroicon::OutlinedEye)
        ->and($icons['hidePassword'])->toBe(Heroicon::OutlinedEyeSlash)
        ->and($field->getEmojiIcon())->toBe(GravityIcon::FaceSmile)
        ->and($field->getMicrophoneIcon())->toBe(GravityIcon::Microphone);
});

it('exposes character counter clearable loading and password strength api', function () {
    $field = FlexTextInput::make('title')
        ->maxLength(120)
        ->characterCounter()
        ->clearable()
        ->loading()
        ->validating();

    expect($field->shouldShowCharacterCounter())->toBeTrue()
        ->and($field->isClearable())->toBeTrue()
        ->and($field->shouldShowLoadingIndicator())->toBeTrue()
        ->and($field->getCharacterLimit())->toBe(120);

    $passwordField = FlexTextInput::make('password')
        ->password()
        ->passwordStrength();

    expect($passwordField->shouldShowPasswordStrength())->toBeTrue();

    expect(FlexTextInput::make('title')->passwordStrength()->shouldShowPasswordStrength())->toBeFalse();
});

it('calculates password strength for server-side rendering', function () {
    $field = FlexTextInput::make('password')->password();

    expect($field->calculatePasswordStrength(''))->toBe([
        'score' => 0,
        'label' => '',
        'percent' => 0,
    ])->and($field->calculatePasswordStrength('secret-password'))->toMatchArray([
        'score' => 1,
        'label' => 'Weak',
        'percent' => 25,
    ]);
});

it('supports custom password strength labels', function () {
    $field = FlexTextInput::make('password')
        ->password()
        ->passwordStrengthLabels([
            'Very weak' => 'Bardzo słabe',
            'Weak' => 'Słabe',
            'Fair' => 'Przeciętne',
            'Good' => 'Dobre',
            'Strong' => 'Silne',
        ]);

    expect($field->getPasswordStrengthLabels())->toBe([
        'Bardzo słabe',
        'Słabe',
        'Przeciętne',
        'Dobre',
        'Silne',
    ])->and($field->calculatePasswordStrength('secret-password'))->toMatchArray([
        'score' => 1,
        'label' => 'Słabe',
        'percent' => 25,
    ]);

    $partialField = FlexTextInput::make('password')
        ->password()
        ->passwordStrengthLabels([
            4 => 'Mocne hasło',
        ]);

    expect($partialField->getPasswordStrengthLabels()[4])->toBe('Mocne hasło')
        ->and($partialField->getPasswordStrengthLabels()[0])->toBe('Very weak');
});

it('rendads emoji picker styles only for an enabled instance capability', function () {
    expect(FlexFieldAssets::stylesheetsFor('flex-text-input'))
        ->toBe(['flex-text-input'])
        ->and(ComponentAssetPlan::for('flex-text-input', ['emoji-picker'])->stylesheets)
        ->toBe(['flex-text-input', 'overlay-runtime', 'teleported-menu', 'emoji-picker'])
        ->and(file_get_contents(__DIR__.'/../../resources/css/components/emoji-picker.css'))
        ->toContain('.fff-emoji-picker__host')
        ->not->toContain('z-[9999]');

    $flexTextInputCss = file_get_contents(__DIR__.'/../../resources/css/components/flex-text-input.css');

    expect($flexTextInputCss)->not->toContain('@import "./emoji-picker.css"');
});

it('neuteps native binding morphable with stable per-instance ownership', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-text-input-field.blade.php');

    expect($blade)
        ->toContain('$livewireKey = $getLivewireKey();')
        ->not->toContain('wire:ignore')
        ->not->toContain('x-init=')
        ->toContain('wire:key="{{ $livewireKey }}.control"');
});

it('supports verification status below the input shell', function () {
    $field = FlexTextInput::make('email')
        ->verificationStatus('Verified 2 Jan, 2027')
        ->verificationStatusColor('primary');

    expect($field->hasVerificationStatus())->toBeTrue()
        ->and($field->getVerificationStatus())->toBe('Verified 2 Jan, 2027')
        ->and($field->getVerificationStatusIcon())->toBe(GravityIcon::SealCheck)
        ->and($field->getVerificationStatusColor())->toBe('primary');
});

it('renders verification status markup in the flex text input blade', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/components/text-input/feedback.blade.php');

    expect($blade)
        ->toContain('fff-flex-text-input__verification-status')
        ->toContain('fff-flex-text-input__verification-status-label')
        ->toContain('$hasVerificationStatus');
});

it('does not restore removed Gravity action icons from previously published configuration', function () {
    config()->set('filament-flex-fields.ui.flex_text_input_emoji_icon', 'gravityui-face-smile');
    config()->set('filament-flex-fields.ui.flex_text_input_microphone_icon', 'gravityui-microphone');
    config()->set('filament-flex-fields.ui.flex_text_input_show_password_icon', 'gravityui-eye');
    config()->set('filament-flex-fields.ui.flex_textarea_emoji_icon', 'gravityui-face-smile');
    config()->set('filament-flex-fields.ui.flex_textarea_microphone_icon', 'gravityui-microphone');
    $input = FlexTextInput::make('input');
    $textarea = FlexTextareaField::make('note');
    expect($input->getEmojiIcon())->toBe('hugeicons-look-top')
        ->and($input->getMicrophoneIcon())->toBe('flexhugeicons-mic-01')
        ->and($input->getShowPasswordIcon())->toBe('hugeicons-view')
        ->and($textarea->getEmojiIcon())->toBe('hugeicons-look-top')
        ->and($textarea->getMicrophoneIcon())->toBe('flexhugeicons-mic-01');
});

it('uses View and ViewOff for password reveal while preserving explicit icon configuration', function () {
    $field = FlexTextInput::make('password')->password()->revealable();
    expect($field->getShowPasswordIcon())->toBe('hugeicons-view')
        ->and($field->getHidePasswordIcon())->toBe('hugeicons-view-off');
    $field->showPasswordIcon('hugeicons-star');
    expect($field->getShowPasswordIcon())->toBe('hugeicons-star');
});
