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

    // Shared CI runners (esp. PHP 8.5) routinely land ~1.0–1.5s; keep a hard ceiling
    // that still catches catastrophic regressions without flake fails.
    $budgetMs = getenv('CI') !== false ? 2500.0 : 1500.0;

    expect($ms)->toBeLessThan($budgetMs);
});

it('keeps Filament default optionsLimit on the 10k playground field (virt bypasses the cap at runtime)', function (): void {
    $playground = app(SelectPlayground::class);

    $flatten = function (array $components) use (&$flatten): array {
        $out = [];

        foreach ($components as $component) {
            $out[] = $component;

            if (method_exists($component, 'getDefaultChildComponents')) {
                $out = [...$out, ...$flatten($component->getDefaultChildComponents())];
            }
        }

        return $out;
    };

    $field = collect($flatten($playground->components()))
        ->first(fn ($component): bool => $component instanceof SelectField
            && $component->getName() === 'select__scale_10k');

    expect($field)->toBeInstanceOf(SelectField::class)
        ->and($field->getOptionsLimit())->toBe(50);
});
