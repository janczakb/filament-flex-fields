<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardGroup;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Actions\Action;

it('exposes item card group configuration via fluent api', function () {
    $group = ItemCardGroup::make('General')
        ->description('Manage your basic account settings')
        ->divided()
        ->variant('outline')
        ->layout('list');

    expect($group->getHeading())->toBe('General')
        ->and($group->getDescription())->toBe('Manage your basic account settings')
        ->and($group->isDivided())->toBeTrue()
        ->and($group->getVariant())->toBe('outline')
        ->and($group->getLayout())->toBe('list')
        ->and($group->getHeaderStyle())->toBe('embedded');
});

it('supports separated as an alias for divided item card groups', function () {
    $group = ItemCardGroup::make('Account')
        ->separated();

    expect($group->isDivided())->toBeTrue();
});

it('disables separators by default and can turn them off explicitly', function () {
    $default = ItemCardGroup::make('Account');

    $disabled = ItemCardGroup::make('Account')
        ->separated()
        ->separated(false);

    $withoutSeparators = ItemCardGroup::make('Account')
        ->separated()
        ->withoutSeparators();

    expect($default->isDivided())->toBeFalse()
        ->and($disabled->isDivided())->toBeFalse()
        ->and($withoutSeparators->isDivided())->toBeFalse();
});

it('supports tertiary variant for item card groups', function () {
    $group = ItemCardGroup::make('Cloud')
        ->variant('tertiary');

    expect($group->getVariant())->toBe('tertiary');
});

it('supports outside header style for item card groups', function () {
    $group = ItemCardGroup::make('Source Control')
        ->headerStyle('outside');

    expect($group->getHeaderStyle())->toBe('outside');
});

it('rejects unsupported item card group header styles', function () {
    ItemCardGroup::make('General')
        ->headerStyle('floating')
        ->getHeaderStyle();
})->throws(InvalidArgumentException::class);

it('supports item card leading image with shape and alt text', function () {
    $card = ItemCard::make('Alex Rivera')
        ->description('Product designer')
        ->image('https://example.com/avatar.jpg')
        ->imageShape('circle')
        ->imageAlt('Alex Rivera avatar')
        ->icon(GravityIcon::Person);

    expect($card->getImage())->toBe('https://example.com/avatar.jpg')
        ->and($card->getImageShape())->toBe('circle')
        ->and($card->getImageAlt())->toBe('Alex Rivera avatar')
        ->and($card->hasLeadingImage())->toBeTrue()
        ->and($card->hasLeadingIcon())->toBeFalse()
        ->and($card->getIcon())->toBeNull();
});

it('defaults item card image alt text to the heading', function () {
    $card = ItemCard::make('Alex Rivera')
        ->image('https://example.com/avatar.jpg');

    expect($card->getImageAlt())->toBe('Alex Rivera')
        ->and($card->getImageShape())->toBe('rounded');
});

it('rejects unsupported item card image shapes', function () {
    ItemCard::make('Profile')
        ->image('https://example.com/avatar.jpg')
        ->imageShape('square')
        ->getImageShape();
})->throws(InvalidArgumentException::class);

it('exposes item card row configuration via fluent api', function () {
    $card = ItemCard::make('Language')
        ->description('Choose your preferred language')
        ->icon(GravityIcon::Globe)
        ->variant('default')
        ->chevron();

    expect($card->getHeading())->toBe('Language')
        ->and($card->getDescription())->toBe('Choose your preferred language')
        ->and($card->getIcon())->toBe(GravityIcon::Globe)
        ->and($card->getVariant())->toBe('default')
        ->and($card->hasChevron())->toBeTrue()
        ->and($card->getChevronIcon())->toBe(GravityIcon::ChevronRight);
});

it('uses gravity ui chevron icon by default', function () {
    expect(ItemCard::make('Language')->getChevronIcon())->toBe(GravityIcon::ChevronRight);
});

it('enables pressable rows on item card groups', function () {
    $group = ItemCardGroup::make('Account')->pressable();

    expect($group->areRowsPressable())->toBeTrue();
});

it('auto enables pressable item cards with chevron and no actions', function () {
    $card = ItemCard::make('Profile')->chevron();

    expect($card->isPressable())->toBeTrue();
});

it('disables pressable item cards when they contain interactive actions', function () {
    $card = ItemCard::make('Language')
        ->chevron()
        ->schema([
            SelectField::make('channel')->options(['email' => 'Email']),
        ]);

    expect($card->isPressable())->toBeFalse();
});

it('supports custom pressable actions on item cards', function () {
    $card = ItemCard::make('Profile')
        ->pressableAction(fn () => null);

    expect($card->isPressable())->toBeTrue()
        ->and($card->getPressableAction())->not->toBeNull()
        ->and($card->getPressableAction()?->getName())->toBe('profile')
        ->and($card->getKey(isAbsolute: false))->toBe('profile');
});

it('derives item card keys from explicit pressable action names', function () {
    $card = ItemCard::make('Security')
        ->pressableAction(
            Action::make('openSecurity')
                ->action(fn () => null),
        );

    expect($card->getKey(isAbsolute: false))->toBe('open-security')
        ->and($card->getPressableAction()?->getName())->toBe('openSecurity');
});

it('returns prepared pressable actions bound to the item card component', function () {
    $card = ItemCard::make('Profile')
        ->pressableAction(fn () => null);

    $action = $card->getPressableAction();

    expect($action?->getSchemaComponent())->toBe($card);
});

it('can explicitly disable pressable item cards', function () {
    $card = ItemCard::make('Profile')
        ->chevron()
        ->pressable(false);

    expect($card->isPressable())->toBeFalse();
});

it('supports standalone and in group contexts for item cards', function () {
    $standalone = ItemCard::make('Profile')->standalone();
    $inGroup = ItemCard::make('Profile')->inGroup();

    expect($standalone->getContext())->toBe('standalone')
        ->and($inGroup->getContext())->toBe('group');
});

it('rejects unsupported item card contexts', function () {
    ItemCard::make('Profile')
        ->context('floating')
        ->getContext();
})->throws(InvalidArgumentException::class);

it('rejects unsupported item card group layouts', function () {
    ItemCardGroup::make('General')
        ->layout('stack')
        ->getLayout();
})->throws(InvalidArgumentException::class);

it('exposes item card stack spacing configuration via fluent api', function () {
    $stack = ItemCardStack::make()
        ->stackGap('lg');

    expect($stack->getStackGap())->toBe('lg');
});

it('rejects unsupported item card stack spacing values', function () {
    ItemCardStack::make()
        ->stackGap('xl')
        ->getStackGap();
})->throws(InvalidArgumentException::class);

it('supports item card select variant', function () {
    $field = SelectField::make('channel')
        ->options(['email' => 'Email'])
        ->variant('item-card');

    expect($field->getVariant())->toBe('item-card')
        ->and($field->isClearable())->toBeFalse()
        ->and($field->canSelectPlaceholder())->toBeFalse()
        ->and($field->getDropdownAlign())->toBe('end');
});

it('allows clearing item card select when explicitly enabled', function () {
    $field = SelectField::make('channel')
        ->options(['email' => 'Email'])
        ->variant('item-card')
        ->clearable();

    expect($field->isClearable())->toBeTrue()
        ->and($field->canSelectPlaceholder())->toBeTrue();
});

it('rejects unsupported item card select variants', function () {
    SelectField::make('channel')
        ->variant('ghost')
        ->getVariant();
})->throws(InvalidArgumentException::class);
