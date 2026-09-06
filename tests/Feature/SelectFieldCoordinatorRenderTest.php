<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Livewire\Livewire;

it('renders headless combobox shell for static select fields by default', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('status')
            ->options([
                'draft' => 'Draft',
                'published' => 'Published',
            ]),
        SelectField::make('technologies')
            ->options([
                'tailwind' => 'Tailwind CSS',
                'laravel' => 'Laravel',
            ])
            ->multiple()
            ->searchable(),
        SelectField::make('theme')
            ->options([
                'sky' => 'Sky',
                'mint' => 'Mint',
            ])
            ->optionLayout('grid')
            ->richOptions(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fffHeadlessSelectField')
        ->toContain('fff-select-field__shell--headless')
        ->toContain('id="form.status"')
        ->toContain('id="form.status-listbox"')
        ->toContain('id="form.technologies-search"')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.status["\']?/')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.technologies["\']?/')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.theme["\']?/')
        ->not->toContain('fffSelectFieldCoordinator')
        ->not->toContain('data-fff-select-root')
        ->not->toContain('selectFormComponent({');
});

it('renders user select through the headless combobox runtime', function (): void {
    TestableTranslatableForm::$formSchema = [
        UserSelect::make('assignee')
            ->options([
                'jane' => [
                    'label' => 'Jane Cooper',
                    'description' => 'jane@example.com',
                    'verified' => true,
                ],
            ])
            ->searchable(),
        UserSelect::make('members')
            ->options([
                'alex' => [
                    'label' => 'Alex Rivera',
                    'description' => 'alex@example.com',
                    'verified' => false,
                ],
            ])
            ->multiple()
            ->searchable(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fff-select-field__shell--headless')
        ->toContain('fffHeadlessSelectField')
        ->toContain('isUserSelectField')
        ->toContain('fff-user-select__selected-tags')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.assignee["\']?/')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.members["\']?/')
        ->not->toContain('fffSelectFieldCoordinator')
        ->not->toContain('data-fff-select-root');
});

it('renders rich option selects through the headless runtime', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('theme')
            ->options([
                'sky' => 'Sky',
                'mint' => 'Mint',
            ])
            ->optionLayout('grid')
            ->richOptions()
            ->searchable(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fffHeadlessSelectField')
        ->toContain('fff-select-field__shell--headless')
        ->not->toContain('fffSelectFieldCoordinator')
        ->not->toContain('data-fff-select-root')
        ->not->toContain('selectFormComponent({');
});

it('renders async search selects through the headless runtime', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('author')
            ->searchable()
            ->getSearchResultsUsing(fn (): array => ['1' => 'Jane']),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fffHeadlessSelectField')
        ->toContain('hasDynamicSearchResults: true')
        ->toContain('componentKey')
        ->not->toContain('selectFormComponent({');
});

it('renders closure option selects as dynamic options without async search', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('status')
            ->options(fn (): array => [
                'draft' => 'Draft',
                'published' => 'Published',
            ])
            ->default('published')
            ->searchable(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('hasDynamicOptions: true')
        ->toContain('hasDynamicSearchResults: false')
        ->toContain('options: []')
        ->toContain('componentKey');
});

it('fetches closure based options through callSchemaComponentMethod', function (): void {
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

    $componentKey = $field->getKey();

    expect($componentKey)->toBeString()->toContain('.');

    $options = $livewire->instance()->callSchemaComponentMethod($componentKey, 'getOptionsForJs');

    expect($options)->toBeArray()->not->toBeEmpty()
        ->and(collect($options)->pluck('value')->all())->toContain('draft', 'published');
});

it('fetches playground dynamic options through callSchemaComponentMethod', function (): void {
    $statusOptions = [
        'draft' => 'Draft',
        'reviewing' => 'Reviewing',
        'published' => 'Published',
    ];

    TestableTranslatableForm::$formSchema = [
        Section::make('SelectField')->schema([
            SelectField::make('select__dynamic_options')
                ->options(fn (): array => $statusOptions)
                ->default('published')
                ->searchable(),
        ]),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->fillForm(['select__dynamic_options' => 'published']);

    $field = $livewire->instance()
        ->getSchema('form')
        ->getComponentByStatePath('select__dynamic_options');

    expect($field)->toBeInstanceOf(SelectField::class)
        ->and($field->hasDynamicOptions())->toBeTrue()
        ->and($field->hasDynamicSearchResults())->toBeFalse();

    $componentKey = $field->getKey();

    expect($componentKey)->toBeString()->toContain('.');

    $options = $livewire->instance()->callSchemaComponentMethod($componentKey, 'getOptionsForJs');

    expect($options)->toBeArray()->not->toBeEmpty()
        ->and(collect($options)->pluck('value')->all())->toContain('published');

    $html = $livewire->html(false);

    expect($html)
        ->toContain('hasDynamicOptions: true')
        ->toContain('hasDynamicSearchResults: false')
        ->toContain('select__dynamic_options')
        ->not->toContain('componentKey: null');
});

it('resolves dependsOn options from a live sibling through getOptionsForJs', function (): void {
    TestableTranslatableForm::$formSchema = [
        Grid::make(['default' => 1, 'lg' => 2])->schema([
            SelectField::make('select__cascade_country')
                ->options([
                    'us' => 'United States',
                    'pl' => 'Poland',
                ])
                ->live()
                ->searchable(),
            SelectField::make('select__cascade_region')
                ->dependsOn('select__cascade_country', fn (?string $country): array => match ($country) {
                    'us' => [
                        'ca' => 'California',
                        'tx' => 'Texas',
                    ],
                    'pl' => [
                        'mz' => 'Mazowieckie',
                    ],
                    default => [],
                })
                ->searchable()
                ->placeholder('Pick a country first'),
        ]),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->fillForm([
            'select__cascade_country' => 'us',
            'select__cascade_region' => null,
        ]);

    $region = $livewire->instance()
        ->getSchema('form')
        ->getComponentByStatePath('select__cascade_region');

    expect($region)->toBeInstanceOf(SelectField::class)
        ->and($region->hasDynamicOptions())->toBeTrue()
        ->and($region->hasClientSideOptionList())->toBeFalse();

    $empty = $livewire->instance()->callSchemaComponentMethod($region->getKey(), 'getOptionsForJs');

    expect(collect($empty)->pluck('value')->all())->toContain('ca', 'tx')
        ->and(collect($empty)->pluck('value')->all())->not->toContain('mz');

    $livewire->fillForm(['select__cascade_country' => 'pl']);

    $poland = $livewire->instance()->callSchemaComponentMethod($region->getKey(), 'getOptionsForJs');

    expect(collect($poland)->pluck('value')->all())->toContain('mz')
        ->and(collect($poland)->pluck('value')->all())->not->toContain('ca');

    $html = $livewire->html(false);

    expect($html)
        ->toContain('hasDynamicOptions: true')
        ->toContain('select__cascade_region');
});

it('required select round-trips filled and empty Livewire state (server path)', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('company_size')
            ->label('Company size')
            ->options([
                '1_10' => '1–10',
                '11_50' => '11–50',
                'other' => 'Other',
            ])
            ->required()
            ->live(),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);

    expect($livewire->get('data.company_size'))->toBeNull();

    $livewire->fillForm(['company_size' => '1_10'])
        ->assertSet('data.company_size', '1_10');

    $livewire->fillForm(['company_size' => null])
        ->assertSet('data.company_size', null);

    $livewire->fillForm(['company_size' => 'other'])
        ->assertSet('data.company_size', 'other');
});

it('required multi select round-trips empty array and values', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('skills')
            ->options([
                'php' => 'PHP',
                'react' => 'React',
            ])
            ->multiple()
            ->required()
            ->live(),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->fillForm(['skills' => ['php', 'react']])
        ->assertSet('data.skills', ['php', 'react'])
        ->fillForm(['skills' => []])
        ->assertSet('data.skills', []);
});

it('headless select blade binds live entangle for wire sync', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('company_size')
            ->options([
                '1_10' => '1–10',
            ])
            ->required()
            ->live(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html();

    expect($html)
        ->toContain('fff-select-field__shell--headless')
        ->toContain('company_size')
        ->toContain('entangle(')
        ->not->toContain('fi-select-input-native');
});
