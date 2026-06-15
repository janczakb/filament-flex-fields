<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

it('extends filament textarea and exposes universal styling api', function () {
    $field = FlexTextareaField::make('message')
        ->placeholder('Write something')
        ->maxLength(500)
        ->characterCounter()
        ->animatedAutosize()
        ->maxHeight('20rem')
        ->footer('Footer copy')
        ->size('lg')
        ->variant('secondary')
        ->toolbarActions([
            Action::make('attach')->label('Attach')->icon(Heroicon::PaperClip),
        ])
        ->suffixAction(
            Action::make('send')->label('Send')->icon(Heroicon::ArrowUp),
        );

    expect($field)->toBeInstanceOf(Textarea::class)
        ->and($field->shouldAutosize())->toBeTrue()
        ->and($field->shouldShowCharacterCounter())->toBeTrue()
        ->and($field->shouldAnimateAutosize())->toBeTrue()
        ->and($field->getMaxHeight())->toBe('20rem')
        ->and($field->getFooter())->toBe('Footer copy')
        ->and($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('secondary')
        ->and($field->getCharacterLimit())->toBe(500)
        ->and($field->getPrefixActions())->toHaveCount(1)
        ->and($field->getSuffixActions())->toHaveCount(1)
        ->and($field->getPrefixActions()['attach']->getName())->toBe('attach')
        ->and($field->getSuffixActions()['send']->getName())->toBe('send');
});

it('exposes focus outline api', function () {
    expect(FlexTextareaField::make('message')->shouldShowFocusOutline())->toBeFalse()
        ->and(FlexTextareaField::make('message')->focusOutline()->shouldShowFocusOutline())->toBeTrue();
});

it('rejects unsupported flex textarea variants', function () {
    FlexTextareaField::make('message')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('includes wrapper classes for size and variant', function () {
    $field = FlexTextareaField::make('message')
        ->size('sm')
        ->variant('flat');

    expect($field->getWrapperClasses())->toBe([
        'fff-flex-textarea-field',
        'fff-flex-textarea-field--sm',
        'fff-flex-textarea-field--flat',
    ]);
});

it('calculates initial height from rows', function () {
    $field = FlexTextareaField::make('message')->rows(4);

    expect($field->getInitialHeightRem())->toBe(6.25);
});

it('calculates rendered height from wrapped content for large size', function () {
    $field = FlexTextareaField::make('message')->size('lg');

    expect($field->getRenderedHeightRem('Large textarea with more room for longer messages.'))
        ->toBeGreaterThan($field->getInitialHeightRem());
});

it('keeps rendered height at minimum for empty content', function () {
    $field = FlexTextareaField::make('message')->rows(4);

    expect($field->getRenderedHeightRem(''))->toBe(6.25);
});

it('registers toolbar and suffix actions in field action registry', function () {
    $field = FlexTextareaField::make('message')
        ->toolbarAction(Action::make('attach'))
        ->suffixAction(Action::make('send'));

    expect($field->getActions())->toHaveKeys(['attach', 'send']);
});

it('does not enable speech dictation by default', function () {
    $field = FlexTextareaField::make('message');

    expect($field->shouldEnableSpeechDictation())->toBeFalse();
});

it('can enable browser speech dictation', function () {
    $field = FlexTextareaField::make('message')
        ->speechDictation()
        ->speechDictationLabel('Speak');

    expect($field->shouldEnableSpeechDictation())->toBeTrue()
        ->and($field->getSpeechDictationLanguage())->toBeNull()
        ->and($field->getSpeechDictationLabel())->toBe('Speak');
});

it('uses gravity ui icons for emoji and microphone by default', function () {
    $field = FlexTextareaField::make('message');

    expect($field->getEmojiIcon())->toBe(GravityIcon::FaceSmile)
        ->and($field->getMicrophoneIcon())->toBe(GravityIcon::Microphone);
});

it('can configure toolbar select with active label state', function () {
    $field = FlexTextareaField::make('message')
        ->toolbarSelect('selected_model', [
            'gpt-5.4' => 'GPT-5.4',
            'claude-4.6-opus' => 'Claude 4.6 Opus',
        ], GravityIcon::Globe, 'Model');

    expect($field->getToolbarSelects())->toHaveCount(1)
        ->and($field->getToolbarSelects()[0]['statePath'])->toBe('selected_model')
        ->and($field->getToolbarSelects()[0]['options']['claude-4.6-opus'])->toBe('Claude 4.6 Opus')
        ->and($field->getToolbarSelects()[0]['placeholder'])->toBe('Model')
        ->and($field->getToolbarSelects()[0]['initialValue'])->toBeNull()
        ->and($field->getToolbarSelects()[0]['initialLabel'])->toBe('Model');
});

it('does not enable emoji picker by default', function () {
    $field = FlexTextareaField::make('message');

    expect($field->shouldEnableEmojiPicker())->toBeFalse();
});

it('can enable emoji picker with locale', function () {
    $field = FlexTextareaField::make('message')
        ->emojiPicker()
        ->emojiPickerLocale('pl')
        ->emojiPickerLabel('Emoji');

    expect($field->shouldEnableEmojiPicker())->toBeTrue()
        ->and($field->getEmojiPickerLocale())->toBe('pl')
        ->and($field->getEmojiPickerLabel())->toBe('Emoji');
});

it('scopes livewire loading targets to each toolbar action', function () {
    $field = FlexTextareaField::make('flex_textarea__basic')
        ->toolbarAction(Action::make('attach'))
        ->suffixAction(Action::make('send'));

    $attach = $field->getPrefixActions()['attach'];
    $send = $field->getSuffixActions()['send'];

    expect($attach->getLivewireTarget())
        ->toBe("mountAction('attach')")
        ->and($send->getLivewireTarget())
        ->toBe("mountAction('send')");
});

it('disables suffix actions when textarea value is empty', function () {
    $field = FlexTextareaField::make('message')
        ->suffixAction(Action::make('send'));

    expect($field->isSubmitDisabled(''))->toBeTrue()
        ->and($field->isSubmitDisabled('Hello'))->toBeFalse()
        ->and($field->getSuffixActions()['send']->getExtraAttributes()['x-bind:disabled'])->toBe('!canSubmit');
});

it('disables submit action when textarea value is empty', function () {
    $field = FlexTextareaField::make('message')
        ->submitAction(Action::make('send'));

    expect($field->getSubmitActionNames())->toBe(['send'])
        ->and($field->isSubmitDisabled(''))->toBeTrue()
        ->and($field->isSubmitDisabled('Hello'))->toBeFalse()
        ->and($field->getSuffixActions()['send']->getExtraAttributes()['x-bind:disabled'])->toBe('!canSubmit');
});
