<?php

declare(strict_types=1);

/**
 * Atomic SelectField presentation/config matrix — tiny schemas only (fast).
 */

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Livewire\Livewire;

it('atomic SelectField config matrix round-trips Livewire state', function (
    bool $multiple,
    bool $searchable,
    bool $clearable,
    bool $live,
    string $size,
    string $variant,
): void {
    $field = SelectField::make('matrix_field')
        ->options([
            'a' => 'Alpha',
            'b' => 'Bravo',
            'c' => 'Charlie',
        ])
        ->multiple($multiple)
        ->searchable($searchable)
        ->clearable($clearable)
        ->size($size)
        ->variant($variant);

    if ($live) {
        $field->live();
    }

    TestableTranslatableForm::$formSchema = [$field];

    $initial = $multiple ? ['a', 'c'] : 'b';
    $next = $multiple ? ['b'] : 'a';
    $empty = $multiple ? [] : null;

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->set('data.matrix_field', $initial)
        ->assertSet('data.matrix_field', $initial)
        ->set('data.matrix_field', $next)
        ->assertSet('data.matrix_field', $next);

    if ($clearable) {
        $livewire->set('data.matrix_field', $empty)
            ->assertSet('data.matrix_field', $empty)
            ->set('data.matrix_field', $initial)
            ->assertSet('data.matrix_field', $initial);
    }
})->with(function () {
    $cases = [];
    $sizes = ['sm', 'md', 'lg'];
    $variants = ['bordered', 'secondary', 'soft', 'flat', 'faded', 'underlined', 'item-card'];

    foreach ([false, true] as $multiple) {
        foreach ([true, false] as $searchable) {
            foreach ([true, false] as $clearable) {
                foreach ([true] as $live) {
                    foreach ($sizes as $size) {
                        foreach ($variants as $variant) {
                            if ($multiple && $variant === 'item-card') {
                                continue;
                            }

                            $cases[] = [$multiple, $searchable, $clearable, $live, $size, $variant];
                        }
                    }
                }
            }
        }
    }

    return $cases;
});

it('atomic create-option single and multiple state round-trips', function (bool $multiple): void {
    $field = SelectField::make('create_field')
        ->options([
            'laravel' => 'Laravel',
            'tailwind' => 'Tailwind',
        ])
        ->multiple($multiple)
        ->searchable()
        ->live();

    TestableTranslatableForm::$formSchema = [$field];

    if ($multiple) {
        Livewire::test(TestableTranslatableForm::class)
            ->set('data.create_field', ['laravel'])
            ->assertSet('data.create_field', ['laravel'])
            ->set('data.create_field', ['laravel', 'custom_brand'])
            ->assertSet('data.create_field', ['laravel', 'custom_brand'])
            ->set('data.create_field', [])
            ->assertSet('data.create_field', []);
    } else {
        Livewire::test(TestableTranslatableForm::class)
            ->set('data.create_field', 'laravel')
            ->assertSet('data.create_field', 'laravel')
            ->set('data.create_field', 'custom_brand')
            ->assertSet('data.create_field', 'custom_brand')
            ->set('data.create_field', null)
            ->assertSet('data.create_field', null);
    }
})->with([false, true]);

it('atomic reorderable multi preserves order through Livewire', function (array $order): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('reorder')
            ->options([
                'tailwind' => 'Tailwind',
                'laravel' => 'Laravel',
                'livewire' => 'Livewire',
                'alpine' => 'Alpine',
            ])
            ->multiple()
            ->reorderable()
            ->live()
            ->searchable(),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->set('data.reorder', $order)
        ->assertSet('data.reorder', $order);
})->with([
    [['tailwind', 'laravel', 'livewire', 'alpine']],
    [['alpine', 'livewire', 'laravel', 'tailwind']],
    [['laravel']],
    [['livewire', 'tailwind']],
    [[]],
]);
