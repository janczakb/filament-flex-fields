<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexVerificationCodePlayground;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

it('exposes verification code configuration via fluent api', function () {
    $field = FlexVerificationCode::make('code')
        ->length(6)
        ->groups([3, 3])
        ->groupSeparator('-')
        ->allowedCharacters('alphanumeric')
        ->size(ControlSize::Md)
        ->color('primary');

    expect($field->getLength())->toBe(6)
        ->and($field->getResolvedGroups())->toBe([3, 3])
        ->and($field->getGroupSeparator())->toBe('-')
        ->and($field->shouldShowSeparators())->toBeTrue()
        ->and($field->getAllowedCharacters())->toBe('alphanumeric')
        ->and($field->isNumeric())->toBeFalse()
        ->and($field->getSize())->toBe('md')
        ->and($field->getColor())->toBe('primary')
        ->and($field->getInputMode())->toBe('text')
        ->and($field->getValidationPattern())->toBe('/^[A-Za-z0-9]{6}$/');
});

it('aliases groupSizes to groups', function () {
    $field = FlexVerificationCode::make('code')
        ->length(8)
        ->groupSizes([4, 4]);

    expect($field->getResolvedGroups())->toBe([4, 4]);
});

it('defaults to a single group when groups are not configured', function () {
    $field = FlexVerificationCode::make('code')->length(6);

    expect($field->getResolvedGroups())->toBe([6])
        ->and($field->shouldShowSeparators())->toBeFalse()
        ->and($field->getGroupSeparator())->toBeNull();
});

it('normalizes numeric verification code state', function () {
    $field = FlexVerificationCode::make('code')->length(6);

    expect($field->normalizeState('52a3 456x'))->toBe('523456');
});

it('normalizes alphanumeric verification code state to uppercase', function () {
    $field = FlexVerificationCode::make('code')
        ->length(6)
        ->allowedCharacters('alphanumeric');

    expect($field->normalizeState('a1-b2c3!'))->toBe('A1B2C3');
});

it('rejects unsupported allowed character sets', function () {
    FlexVerificationCode::make('code')->allowedCharacters('hex')->getAllowedCharacters();
})->throws(InvalidArgumentException::class);

it('rejects groups that do not sum to the configured length', function () {
    FlexVerificationCode::make('code')
        ->length(6)
        ->groups([2, 2])
        ->getResolvedGroups();
})->throws(InvalidArgumentException::class);

it('includes wrapper classes for size and color', function () {
    $field = FlexVerificationCode::make('code')
        ->size('lg')
        ->color('primary');

    expect($field->getWrapperClasses())->toBe([
        'fff-verification-code',
        'fff-verification-code--lg',
        'fi-color-primary',
    ]);
});

it('validates incomplete verification codes', function () {
    $field = FlexVerificationCode::make('code')
        ->length(6)
        ->required();

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('code', '12345', function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.verification_code.incomplete', ['length' => 6]));
});

it('validates invalid verification code characters', function () {
    $field = FlexVerificationCode::make('code')
        ->length(6)
        ->allowedCharacters('numeric');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('code', '12345a', function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.verification_code.invalid_characters'));
});

it('supports eight digit codes split into four and four groups', function () {
    $field = FlexVerificationCode::make('code')
        ->length(8)
        ->groups([4, 4])
        ->groupSeparator('-');

    expect($field->getLength())->toBe(8)
        ->and($field->getResolvedGroups())->toBe([4, 4])
        ->and($field->getValidationPattern())->toBe('/^\d{8}$/');
});

it('exposes auto submit and loading configuration', function () {
    $field = FlexVerificationCode::make('code')
        ->length(6)
        ->autoSubmitMethod('verifyCode')
        ->loading();

    expect($field->shouldAutoSubmit())->toBeTrue()
        ->and($field->getAutoSubmitMethod())->toBe('verifyCode')
        ->and($field->shouldShowLoadingIndicator())->toBeTrue()
        ->and($field->shouldAutoSubmitUsingServerCallback())->toBeFalse();
});

it('enables live updates when submitUsing is configured', function () {
    $field = FlexVerificationCode::make('code')
        ->length(6)
        ->submitUsing(fn (string $code): string => $code);

    expect($field->shouldAutoSubmit())->toBeTrue()
        ->and($field->shouldAutoSubmitUsingServerCallback())->toBeTrue();
});

it('supports heading description and footer chrome', function () {
    $field = FlexVerificationCode::make('otp')
        ->heading('Verify account')
        ->description("We've sent a code to a****@gmail.com")
        ->footer("Didn't receive a code?");

    expect($field->getHeading())->toBe('Verify account')
        ->and($field->getDescription())->toBe("We've sent a code to a****@gmail.com")
        ->and($field->getFooter())->toBe("Didn't receive a code?")
        ->and($field->hasHeaderContent())->toBeTrue()
        ->and($field->hasFooterContent())->toBeTrue()
        ->and($field->hasLayoutChrome())->toBeTrue();
});

it('registers a link-style footer action', function () {
    $field = FlexVerificationCode::make('otp')
        ->footerAction(
            Action::make('resend')
                ->label('Resend')
                ->action(fn () => Notification::make()->title('Sent')->success()->send()),
        );

    expect($field->getFooterAction()?->getName())->toBe('resend')
        ->and($field->getFooterAction()?->isLink())->toBeTrue()
        ->and($field->hasFooterAction())->toBeTrue();
});

it('creates a default resend footer action from a closure', function () {
    $field = FlexVerificationCode::make('otp')
        ->footerAction(fn () => null);

    expect($field->getFooterAction()?->getName())->toBe('otp-footer-action')
        ->and($field->getFooterAction()?->getLabel())->toBe(__('filament-flex-fields::default.verification_code.resend'))
        ->and($field->getFooterAction()?->isLink())->toBeTrue();
});

it('registers verification code playground variants', function () {
    $state = (new FlexVerificationCodePlayground)->defaultState();

    expect($state)->toHaveKeys([
        'verification_code__account_verify',
        'verification_code__default',
        'verification_code__gap_only',
        'verification_code__four_by_four',
        'verification_code__alphanumeric',
        'verification_code__auto_submit_method',
        'verification_code__auto_submit_callback',
        'verification_code__sm',
        'verification_code__md',
        'verification_code__lg',
    ]);
});
