<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Playground\SelectPlayground;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Livewire\Livewire;

it('does not resolve closure based select options during initial render', function (): void {
    $invocations = 0;

    TestableTranslatableForm::$formSchema = [
        SelectField::make('status')
            ->options(function () use (&$invocations): array {
                $invocations++;

                return [
                    'draft' => 'Draft',
                    'published' => 'Published',
                ];
            })
            ->default('published')
            ->searchable(),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->fillForm(['status' => 'published'])
        ->html(false);

    expect($invocations)->toBe(0);
});

it('still resolves closure based select options through callSchemaComponentMethod', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('status')
            ->options(fn (): array => [
                'draft' => 'Draft',
                'published' => 'Published',
            ])
            ->default('published')
            ->searchable(),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->fillForm(['status' => 'published']);

    $field = $livewire->instance()
        ->getSchema('form')
        ->getComponentByStatePath('status');

    expect($field)->toBeInstanceOf(SelectField::class);

    $options = $livewire->instance()->callSchemaComponentMethod($field->getKey(), 'getOptionsForJs');

    expect($options)->toBeArray()->not->toBeEmpty()
        ->and(collect($options)->pluck('value')->all())->toContain('draft', 'published');
});

it('renders the unified select playground quickly', function (): void {
    $playground = app(SelectPlayground::class);

    TestableTranslatableForm::$formSchema = $playground->components();

    $start = hrtime(true);

    Livewire::test(TestableTranslatableForm::class)
        ->call('mountWithPlaygroundState', $playground->defaultState())
        ->html(false);

    $ms = (hrtime(true) - $start) / 1_000_000;

    expect($ms)->toBeLessThan(500);
});
