<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Actions\Action;
use Bjanczak\FilamentFlexFields\Filament\Actions\ActionGroup;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;

it('extends filament action and applies item card styling attributes', function () {
    $action = Action::make('connect')
        ->itemCard();

    expect($action)->toBeInstanceOf(Filament\Actions\Action::class)
        ->and($action->isItemCardAction())->toBeTrue()
        ->and($action->isOutlined())->toBeTrue()
        ->and($action->getSize())->toBe(Size::Small)
        ->and($action->getExtraAttributes())->toHaveKey('class', 'fff-item-card-action');
});

it('can disable item card styling with a closure', function () {
    $action = Action::make('connect')
        ->itemCard(fn (): bool => false);

    expect($action->isItemCardAction())->toBeFalse()
        ->and($action->getExtraAttributes())->not->toHaveKey('class');
});

it('configures hold confirm and disables the default livewire click handler', function () {
    $action = Action::make('delete')
        ->holdConfirm(2000, 'left')
        ->action(fn () => null);

    expect($action->hasHoldConfirm())->toBeTrue()
        ->and($action->getHoldConfirmDuration())->toBe(2000)
        ->and($action->getHoldConfirmSweep())->toBe('left')
        ->and($action->isLivewireClickHandlerEnabled())->toBeFalse()
        ->and($action->getView())->toBe('filament-flex-fields::actions.hold-confirm');
});

it('rejects unsupported hold confirm sweep directions', function () {
    Action::make('delete')
        ->holdConfirm(1000, 'diagonal')
        ->getHoldConfirmSweep();
})->throws(InvalidArgumentException::class);

it('applies item card styling to action group triggers', function () {
    $group = ActionGroup::make([
        Action::make('sync'),
    ])
        ->label('Manage')
        ->button()
        ->itemCard();

    expect($group->isItemCardAction())->toBeTrue()
        ->and($group->getExtraAttributes())->toHaveKey('class', 'fff-item-card-action');
});

it('renders hold confirm layers in button html before alpine boots', function () {
    $action = Action::make('delete')
        ->label('Hold to Delete')
        ->icon(Heroicon::OutlinedTrash)
        ->holdConfirm()
        ->action(fn () => null);

    $html = $action->renderHoldConfirmTriggerHtml();

    expect($html)
        ->toContain('fff-hold-confirm-action__overlay')
        ->toContain('fff-hold-confirm-action__base')
        ->toContain('data-fff-hold-layers-ready="true"')
        ->toContain('Hold to Delete');
});

it('renders hold confirm wrapper view with alpine form component name', function () {
    $action = Action::make('delete')
        ->holdConfirm()
        ->action(fn () => null);

    $html = $action->toHtml();

    expect($html)
        ->toContain('fff-hold-confirm-action-host')
        ->toContain('holdConfirmActionFormComponent(')
        ->toContain('duration: 2000')
        ->toContain('fff-hold-complete')
        ->toContain('mountAction')
        ->toContain('delete')
        ->not->toContain('partials.load-stylesheet');
});

it('includes mount action expression in hold confirm complete handler', function () {
    $action = Action::make('delete')
        ->holdConfirm()
        ->action(fn () => null);

    expect($action->getHoldConfirmCompleteExpression())
        ->toContain("mountAction('delete')");
});

it('can configure rounded corners on any action', function () {
    $action = Action::make('save')
        ->rounded('full');

    expect($action->getRounded())->toBe('full')
        ->and($action->getRoundedClass())->toBe('fff-action--rounded-full')
        ->and($action->getExtraAttributes())->toHaveKey('class', 'fff-action--rounded-full');
});

it('applies rounded corners to hold confirm actions', function () {
    $action = Action::make('delete')
        ->holdConfirm()
        ->rounded('full')
        ->action(fn () => null);

    $html = $action->renderHoldConfirmTriggerHtml();

    expect($html)
        ->toContain('fff-action--rounded-full')
        ->and(substr_count($html, 'fff-action--rounded-full'))->toBe(1);
});

it('rejects unsupported rounded values', function () {
    Action::make('delete')
        ->rounded('pill')
        ->getRounded();
})->throws(InvalidArgumentException::class);

it('defaults hold confirm danger actions to full rounded corners', function () {
    $action = Action::make('delete')
        ->color('danger')
        ->holdConfirm()
        ->action(fn () => null);

    expect($action->getRounded())->toBe('full')
        ->and($action->renderHoldConfirmTriggerHtml())->toContain('fff-action--rounded-full');
});

it('applies danger palette classes when hold confirm is themed', function () {
    $action = Action::make('delete')
        ->color('danger')
        ->holdConfirm()
        ->action(fn () => null);

    expect($action->getHoldConfirmPalette())->toBe('danger')
        ->and($action->getHoldConfirmTriggerClasses())->toContain('fff-hold-confirm-action--palette-danger');
});

it('can disable hold confirm theming to keep filament colors', function () {
    $action = Action::make('delete')
        ->color('danger')
        ->holdConfirm()
        ->holdConfirmThemed(false)
        ->action(fn () => null);

    expect($action->getHoldConfirmPalette())->toBeNull()
        ->and($action->getHoldConfirmTriggerClasses())->not->toContain('fff-hold-confirm-action--palette-danger');
});
