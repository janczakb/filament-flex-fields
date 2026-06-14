<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

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

it('exposes focus outline api', function () {
    expect(FlexTextInput::make('title')->shouldShowFocusOutline())->toBeFalse()
        ->and(FlexTextInput::make('title')->focusOutline()->shouldShowFocusOutline())->toBeTrue()
        ->and(FlexTextInput::make('title')->focusOutline(false)->shouldShowFocusOutline())->toBeFalse();
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

it('uses gravity ui icons for copy and password suffix actions by default', function () {
    $field = FlexTextInput::make('token')
        ->password()
        ->revealable()
        ->copyable();

    $icons = collect($field->getSuffixActions())
        ->mapWithKeys(fn ($action) => [$action->getName() => $action->getIcon()])
        ->all();

    expect($icons['copy'])->toBe(GravityIcon::Copy)
        ->and($icons['showPassword'])->toBe(GravityIcon::Eye)
        ->and($icons['hidePassword'])->toBe(GravityIcon::EyeClosed);
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
        'score' => 3,
        'label' => 'Good',
        'percent' => 75,
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
        'score' => 3,
        'label' => 'Dobre',
        'percent' => 75,
    ]);

    $partialField = FlexTextInput::make('password')
        ->password()
        ->passwordStrengthLabels([
            4 => 'Mocne hasło',
        ]);

    expect($partialField->getPasswordStrengthLabels()[4])->toBe('Mocne hasło')
        ->and($partialField->getPasswordStrengthLabels()[0])->toBe('Very weak');
});

it('renders inline affix focus handler for prefix icon fields', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-text-input-field.blade.php');

    expect($blade)
        ->toContain('focusInputFromAffix()')
        ->toContain('onInput($event)')
        ->toContain('fi-input-has-inline-prefix');
});

it('renders email fields as text inputs with email inputmode for caret support', function () {
    $field = FlexTextInput::make('email')->email();

    expect($field->getType())->toBe('text')
        ->and($field->isEmail())->toBeTrue()
        ->and($field->getInputMode())->toBe('email');
});

it('renders numeric fields as text inputs for caret support', function () {
    $field = FlexTextInput::make('amount')->numeric();

    expect($field->getType())->toBe('text')
        ->and($field->isNumeric())->toBeTrue()
        ->and($field->getInputMode())->toBe('decimal');
});

it('includes emoji picker panel styles via lazy dependency bundle', function () {
    expect(FlexFieldAssets::stylesheetsFor('flex-text-input'))
        ->toBe(['emoji-picker', 'flex-text-input'])
        ->and(file_get_contents(__DIR__.'/../../resources/css/components/emoji-picker.css'))
        ->toContain('.fff-emoji-picker__panel');

    $flexTextInputCss = file_get_contents(__DIR__.'/../../resources/css/components/flex-text-input.css');

    expect($flexTextInputCss)->not->toContain('@import "./emoji-picker.css"');
});

it('isolates alpine root from livewire morphing', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-text-input-field.blade.php');

    expect($blade)
        ->toContain('$livewireKey = $getLivewireKey();')
        ->toContain('wire:ignore')
        ->toContain('wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize()])), 0, 64) }}"');
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
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-text-input-field.blade.php');

    expect($blade)
        ->toContain('fff-flex-text-input__verification-status')
        ->toContain('fff-flex-text-input__verification-status-label')
        ->toContain('$hasVerificationStatus');
});
